<!DOCTYPE html>
<html>
<head>
    <title>Foo CRUD Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h1>Foo CRUD Dashboard</h1>
    <form action="{{ route('foocrud.createEntity') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Entity Name:</label>
            <input type="text" class="form-control" id="name" name="name">
        </div>
        <button type="submit" class="btn btn-primary">Add Entity</button>
    </form>

    <h2>Entities</h2>
    @foreach ($entities as $entity)
        <h3>{{ $entity->name }}</h3>
        <form action="{{ route('foocrud.addField', $entity->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Field Name:</label>
                <input type="text" class="form-control" id="name" name="name">
                <label for="type">Field Type:</label>
                <input type="text" class="form-control" id="type" name="type">
            </div>
            <button type="submit" class="btn btn-secondary">Add Field</button>
        </form>
    @endforeach

    <form action="{{ route('foocrud.generateFiles') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-success">Generate Files</button>
    </form>
</div>
</body>
</html>
