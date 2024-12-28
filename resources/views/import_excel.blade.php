
@extends('dashboard.index')
@section('title', 'Home Page')
@section('content')
<br>
<br>
<h3>Upload a File</h3>
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

<form action="{{ route('import-excelp') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="form-group">
        <label for="file">Select a File:</label>
        <input type="file" class="form-control-file" id="file" name="excel_file" required>
    </div>
    <button type="submit" class="btn btn-primary">Importar</button>
</form>
@endsection
