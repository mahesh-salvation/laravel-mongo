<!-- carindex.blade.php -->

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Index Page</title>
    <link rel="stylesheet" href="{{asset('css/app.css')}}">
  </head>
  <body>
    <div class="container">
    <br />
    @if (\Session::has('success'))
      <div class="alert alert-success">
        <p>{{ \Session::get('success') }}</p>
      </div><br />
     @endif
    <table class="table table-striped">
    <thead>
      <tr>
        <th>Content Id</th>
        <th>User Id</th>
        <th>Content Type</th>
        <th>View Time</th>
        <th>Clicks</th>
        <th>Created At</th>
        <th>Updated At</th>
      </tr>
    </thead>
    <tbody>
      
      @foreach($data as $item)
      <tr>
        <td>{{$item->content_id}}</td>
        <td>{{$item->user_id}}</td>
        <td>{{$item->content_type}}</td>
        <td>{{$item->time}}</td>
        <td>{{$item->clicks}}</td>
        <td>{{$item->created_at}}</td>
        <td>{{$item->updated_at}}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  </div>
  </body>
</html>