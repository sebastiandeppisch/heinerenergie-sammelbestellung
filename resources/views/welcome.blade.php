<!DOCTYPE html>
<html lang="">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <title>Heinerenergie Sammelbestellung</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/js/app.ts'])
  </head>
  <body class="dx-viewport">
    <noscript>
      <strong>We're sorry but <%= htmlWebpackPlugin.options.title %> doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
    </noscript>
    <div id="app"></div>
    @auth
      <script>window.user = @json(Auth::user());</script>
    @endauth
    @guest
      <script>window.user = null;</script>
    @endguest 
  </body>
</html>
