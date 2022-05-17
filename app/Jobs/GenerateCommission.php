<?php

namespace App\Jobs;

use App\Models\ReferalDepositDetails;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCommission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;
    private $refered_upper_users = [];
	private $refered_upper_level = 0;
	private $refered_upper_maxlevel = 2;

    /**
     * Create a new job instance.
     *
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
       
        $payment = Payment::query()->find($this->id);
        $user       =   $payment->user;
        $referredby =   $user->referredby;

        if(!empty($referredby)){
		
            $user_id = $user->id;

            $refered_by_upper_level = $user->refered_by_upper_level;
            if(empty($refered_by_upper_level)){
                $refered_by_upper_level = $this->getUpperUser($user_id);
                $user->refered_by_upper_level = $refered_by_upper_level;
                $user->save();
            }

            
            if(!empty($refered_by_upper_level)){
                $refered_by_upper_level_array = json_decode($refered_by_upper_level);
                foreach($refered_by_upper_level_array AS $key => $value){

                    $deposited_amount = $payment->amount;

                    if( $deposited_amount > 0 ){

                        if($value){
                            $influncer_data = User::query()
                            ->select('id','promoter_type')
                            ->where( 'id', $value )
                            ->where('is_locked', 0)
                            ->first();

                            if ( !empty($influncer_data) ) {

                                if( $key == 1 ) {
                                    if ($influncer_data->promoter_type == 1 ) { // Master
                                        $percentage = 5;
                                    } else if ($influncer_data->promoter_type == 2 ) { //Promoter
                                        $percentage = 5;
                                    } else { // User
                                        $percentage = 2;
                                    }
                                } else if( $key == 2 ) {
                                    if ($influncer_data->promoter_type == 1 ) { // Master
                                        $percentage = 3;
                                    } else if ($influncer_data->promoter_type == 2 ) { //Promoter
                                        $percentage = 0;
                                    } else { // User
                                        $percentage = 0;
                                    }
                                }

                                if ( $percentage > 0 ) {
                                    $user_comission = round(($percentage/100)*$deposited_amount,2);
                                    $entity	=	new ReferalDepositDetails;
                                    $entity->user_id				=	$value;
                                    $entity->earn_by				=	$user_id;
                                    $entity->deposited_amount		=	$deposited_amount;
                                    $entity->payment_id			    =	$payment->id;
                                    $entity->referal_level			=	$key;
                                    $entity->referal_percentage		=	$percentage;
                                    $entity->amount		            =	$user_comission;
                                    $entity->date		            =	date('Y-m-d');
                                    $entity->save();
                                }
                            }
                        }
                    }
                }
            }
		}
    }

    private function getUpperUser($user_id){
		
		
		$this->refered_upper_level++;
		if($this->refered_upper_level > $this->refered_upper_maxlevel){
			$refered_upper_users = $this->refered_upper_users;
			
			if(!empty($refered_upper_users)){
				$refered_upper_users_json = json_encode($refered_upper_users);
			} else {
				$refered_upper_users_json = '';
			}
			$this->refered_upper_level = 0;
			$this->refered_upper_users = [];
			return $refered_upper_users_json;
		}

        $user = User::query()
        ->where( 'id', $user_id )
        ->first();

		if(!empty($user)){
			$referral_id = $user->referral_id;
			$this->refered_upper_users[$this->refered_upper_level] = $referral_id;
			return $this->getUpperUser($referral_id);
		} else {
			$refered_upper_users = $this->refered_upper_users;
			if(!empty($refered_upper_users)){
				$refered_upper_users_json = json_encode($refered_upper_users);
			} else {
				$refered_upper_users_json = '';
			}
			$this->refered_upper_level = 0;
			$this->refered_upper_users = [];
			return $refered_upper_users_json;
		}
	}

}
