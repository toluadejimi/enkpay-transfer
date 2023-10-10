<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Untitled</title>
    <link rel="stylesheet" href="{{url('')}}/public/assets/bootstrap/css/bootstrap.min.css">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="{{url('')}}/public/assets/css/dh-row-text-image-right.css">
    <link rel="stylesheet" href="{{url('')}}/public/assets/css/Footer-Dark-icons.css">
    <link rel="stylesheet" href="{{url('')}}/public/assets/css/Footer-with-social-media-icons.css">
</head>

<body>
    <div class="container">
        <div class="col">
            <div class="card" style="--bs-body-bg: #ededed;--bs-light: #cecfd0;--bs-light-rgb: 206,207,208;"></div>
        </div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-4"></div>
            <div class="col-md-4" style="background: url(&quot;{{url('')}}/public/assets/img/email%20barnner.webp&quot;) center;margin-bottom: 0px;padding: 54px;padding-right: 15px;padding-left: 15px;"><img class="flex-sm-fill" style="width: 99%;transform: translate(1px);padding: 0px;height: 252px;padding-top: 0;padding-bottom: 0px;padding-right: 0;padding-left: 0;margin-top: -13px;" src="{{url('')}}/public/assets/img/email%20barnner.webp" width="382" height="271"></div>
            <div class="col"></div>
        </div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-4"></div>
            <div class="col-md-4" style="text-align: left;padding: 26px;padding-bottom: 17;padding-left: 74px;padding-right: 0px;border-color: var(--bs-card-bg);background: #f2f2f2;">
                <h1 style="font-size: 32px;"><strong>Airtime Purchase&nbsp;</strong></h1>

                <p style="font-size: 14px;font-weight: bold;"> Hi, {{ $data1["first_name"] }}</p>

                <p>We like to inform you that  NGN {{number_format($data1["amount"], 2)}}  has been sent to  {{ $data1["phone"] }}</p>

                <p class="text-muted mb-0" style="--bs-body-font-weight: normal;height: 0px;width: 566px;text-align: left;font-size: 11px;">Thanks for choosing Enkpay</p>

                <br><br>

                <p class="text-muted mb-0" style="--bs-body-font-weight: normal;height: 0px;width: 566px;text-align: left;font-size: 11px;">Copyright © 2023 ENKWAVE</p>
                <p style="color: var(--bs-black);text-align: left;background: #ffffff;"></p>
            </div>
            <div class="col-md-4"></div>
        </div>
    </div>
    <script src="{{url('')}}/public/assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>



