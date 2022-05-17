<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{config('app.name')}}</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css"
          integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    <!-- Styles -->
    <style>
        html, .row {
            height: 100vh;
        }

        body {
            background: #0a1541;
        }
    </style>
</head>
<body>
<br>
<div class="container">
    <div class="row justify-content-center align-items-center">
        <div class="col-12 col-md-4">
            <div class="card m-auto shadow border-0">
                <div class="card-body">
                    <form action="{{route('password.update')}}" method="POST">
                        <h3>Reset Password</h3>
                        <br>
                        {{csrf_field()}}
                        <input type="hidden" name="token" value="{{request('token')}}">
                        <div class="form-group">
                            <label for="email">Enter email</label>
                            <input id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{request('email')}}" required>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="password">Enter new password</label>
                            <input id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required type="password">
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Confirm new password</label>
                            <input id="password_confirmation" name="password_confirmation" class="form-control"
                                   required type="password">
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Reset</button>
                    </form>
                </div>

            </div>

        </div>
    </div>
</div>
</body>
</html>
