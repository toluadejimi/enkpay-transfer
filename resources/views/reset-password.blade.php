<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>ENKPAY RESET PASSWORD</title>
    <link rel="stylesheet" href="{{url('')}}/public/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
    <link rel="stylesheet" href="{{url('')}}/public/assets/css/Login-Form-Clean.css">
    <link rel="stylesheet" href="{{url('')}}/public/assets/css/styles.css">
</head>

<body style="background: #18003d;height: 80;">


    <div class="container">
        <section class="login-clean"
            style="color: var(--bs-gray-100);background: rgba(241,247,252,0);text-align: center;"><img
                class="bounce animated" src="{{url('')}}/public/assets/img/clipboard-image.png"
                style="height: 84px;margin-bottom: 49px;margin-top: 32px;">


            <form method="post" action="reset-password-now"
                style="margin-bottom: 25px;box-shadow: 1px 4px 20px rgba(0,0,0,0.19);border-radius: 10px;">
                @csrf


                <h2 class="visually-hidden">Reset Password Form</h2>
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if (session()->has('message'))
                <div class="alert alert-success">
                    {{ session()->get('message') }}
                </div>
                @endif
                @if (session()->has('error'))
                <div class="alert alert-danger">
                    {{ session()->get('error') }}
                </div>
                @endif
                <h1
                    style="background: rgba(255,255,255,0);border-width: 88px;height: 44px;width: 248px;font-size: 30px;color: #0b0032;margin-bottom: 19px;margin-top: 13px;">
                    RESET PASSWORD</h1>
                <div class="illustration"></div>
                <input name="email" hidden value="{{$email}}">

                <div class="illustration"></div>
                <div class="mb-3"><input class="form-control" min="0" max="500" type="password"  value="" name="password" placeholder="Enter your new passsword"
                        required ></div>
                <div class="mb-3"><input class="form-control" min="0" max="500" type="password" name="password_confirmation" required  placeholder="Confirm your password"
                        style="height: 42px;"></div>


                <div class="mb-3"><button class="btn btn-primary d-block w-100" data-bss-hover-animate="pulse"
                        type="submit" style="background: #0f0141;padding: 13px;margin-top: 46px;">Reset Password</button></div>

            </form><small style="margin-top: 8px;text-align: right;"><br><strong>© 2023 Enkwave Dynamic
                    Technologies</strong><br></small>
        </section>
    </div>
    <script src="{{url('')}}/public/assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="{{url('')}}/public/assets/js/bs-init.js"></script>
</body>

</html>
