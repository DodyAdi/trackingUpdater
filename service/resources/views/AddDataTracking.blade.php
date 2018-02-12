<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Update Tracking</title>
  <script src="{{ asset('js/jquery-3.2.1.min.js') }}"></script>
  <script src="{{ asset('js/bootstrap.min.js') }}"></script>
  <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/js/dropify.min.js"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-waitingfor/1.2.4/bootstrap-waitingfor.min.js" charset="utf-8"></script>
</head>
<body>
  <!-- <form action="{{url('upload')}}" method="post" enctype="multipart/form-data" id="">
    <input type="file" name="file"/>
    {{csrf_field()}}
  </form> -->
  <section>
    <form action="{{url('upload')}}" enctype="multipart/form-data" method="post">
      <input type="file" name="file" class="dropify" data-max-file-size="1M" data-allowed-file-extensions="csv xls xlsx"/>
      {{csrf_field()}}
      <button type="submit"  class="btn btn-info " name="button" style="width:100%;" >Upload</button>
    </form>
  </section>

  </body>

  <script type="text/javascript">
  $('.btn').on('click',function() {
    if ($('input[name="file"]').val() != '') {
      waitingDialog.show('Memproses Data, Jangan Menutub Tab',{
        headerClass: 'center'
      });
    }
  });
  $('.dropify').dropify({
    messages: {
        'default': 'Drag and drop a file here or click',
        'replace': 'Drag and drop or click to replace',
        'remove':  'Hapus',
        'error':   'Ooops, Terjadi Kesalahan'
    }
  });
  </script>
</html>
