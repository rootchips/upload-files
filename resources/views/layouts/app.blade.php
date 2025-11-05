<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Roslan Saidi</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-200 text-gray-800">
  <main class="container mx-auto py-8">
      <div id="app">
            @yield('content')
      </div>
  </main>
</body>
</html>