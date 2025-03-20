<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <title>Heinerenergie CRM</title>
    @vite(['resources/js/app.ts'])
    @routes
    @inertiaHead
  </head>
  <body class="dx-viewport">
    @inertia
  </body>
</html>
