<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('slug');
            $table->longText('content');
            $table->timestamps();
        });

        Page::query()->insert([
            [
                'title' => 'About Us',
                'slug' => 'about',
                'content' => ''
            ],
            [
                'title' => 'Legality',
                'slug' => 'legality',
                'content' => ''
            ],
            [
                'title' => 'Terms and Conditions',
                'slug' => 'terms',
                'content' => ''
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'content' => ''
            ],
            [
                'title' => 'Community guidelines',
                'slug' => 'community-guidelines',
                'content' => ''
            ],
            [
                'title' => 'How to play',
                'slug' => 'how-to-play',
                'content' => ''
            ],
            [
                'title' => 'Responsible Play',
                'slug' => 'responsible-play',
                'content' => ''
            ],
            [
                'title' => 'How It Works',
                'slug' => 'how-it-works',
                'content' => ''
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pages');
    }
}
