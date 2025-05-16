<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Failed</title>
    <link href='https://fonts.googleapis.com/css?family=Lato:300,400|Montserrat:700' rel='stylesheet' type='text/css'>
    <link href="{{asset('assets/admin/css/success-failed.css')}}" rel="stylesheet" type="text/css">
</head>
<body>
<header class="site-header" id="header">
    <h1 class="site-header__title" data-lead-id="site-header-title">{{trans('Sorry! Payment Failed')}}</h1>
</header>

<div class="main-content">
    <i class="fa fa-times main-content__times" id="checkmark"></i>
    <p class="main-content__body" data-lead-id="main-content-body">@lang("We encountered an issue while processing your payment.')
		{{ session('error') ?? __('If you continue to experience this issues, please reach out to our customer support team. We apologize for the inconvenience and appreciate your patience.')}}</p>
</div>
<footer class="site-footer" id="footer">
    <a href="{{ url('/') }}">@lang("Go back to Home")</a>
    <p class="site-footer__fineprint" id="fineprint">@lang("Copyright") ©{{ date('Y') }}  {{trans('All Rights Reserved')}} <a href="{{ url('/') }}">{{ $basic->site_title ?? 'IsabiPay Escrow' }}</a></p>
</footer>
</body>
</html>
