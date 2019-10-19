<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Barcodecitos</title>

    <style>
    body {
  margin: 40px;
}

.grid { 
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  grid-gap: 50px;
  align-items: stretch;
  }
.grid section {
  max-width: 100%;
}
    </style>

</head>

<body>
    <div class="grid">
        @foreach($codes as $barcode)
        <section>
            {!! $barcode !!}
        </section>
        @endforeach
    </div>
</body>

</html>