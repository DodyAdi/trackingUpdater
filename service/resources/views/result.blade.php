<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style media="screen">
  </style>
  <title>Update Tracking</title>
  <script src="{{ asset('js/jquery-3.2.1.min.js') }}"></script>
  <script src="{{ asset('js/bootstrap.min.js') }}"></script>
  <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
  <script src="https://www.w3schools.com/lib/w3.js"></script>
</head>
<body>
  <div class="form-group">
    <div class="input-group">
      <input type="text" class="form-control" id="myInput" oninput="w3.filterHTML('#myTable', '.item', this.value)" placeholder="Search for names.." title="Type in a name">
      <div class="input-group-btn">
        <button class="btn btn-default" type="submit">
          <i class="glyphicon glyphicon-search"></i>
        </button>
      </div>
    </div>
  </div>
  <table class="table table-striped table-hover table-bordered" id="myTable">
    <thead>
      <th>Order Id</th>
      <th>Shopify Fulfillment Status</th>
      <th>Paypal Transactions Id</th>
      <th>Paypal Fulfillment Status</th>
    </thead>
    <tbody>
      @foreach ($data as $value)
      <tr class="item">
        <td>{{ $value['order_id'] }}</td>
        @if ($value['fulfillment_status'] == 201)
          <td>  Fulfillment Success </td>
        @elseif ($value['fulfillment_status'] == 422)
          <td>  Already Fulfilled </td>
        @else
        <td>  Fulfilled Unsuccessful </td>
        @endif

        <td>{{ $value['transaction_id'] }}</td>

        @if ($value['paypalAddTracking_status'] == 201)
          <td>  Add Tracking Success </td>
        @elseif ($value['paypalAddTracking_status'] == 400)
          <td>  Already Has Tracking </td>
        @else
          <td>  Add Tracking Unsuccessful </td>
        @endif
        <td></td>
      </tr>
      @endforeach

    </tbody>
  </table>
</body>
<script type="text/javascript">

</script>
</html>
