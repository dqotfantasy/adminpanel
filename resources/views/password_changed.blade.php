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
            background: #ffc107;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center align-items-center">
        <div class="col-12 col-md-6">
            <div class="card m-auto shadow border-0">
                <div class="card-body text-center">
                    <h2 class="text-success">Success</h2>
                    <h5 class="text-muted">Your password has been changed.</h5>
                </div>

            </div>

        </div>
    </div>
</div>

</body>
</html>
