@extends('themes.default1.admin.layout.kb')
@section('content')
  <!-- open a form -->

{!! Form::model($faq,['url' => 'post-create-faq/'.$faq->id, 'method' => 'PATCH','files'=>true]) !!}

  <!-- <div class="form-group {{ $errors->has('company_name') ? 'has-error' : '' }}"> -->
<!-- table  -->

<div class="row">
  <div class="col-md-12">
    <div class="box box-primary">
      <div class="content-header">

        <div>
          <h4>Faqs {!! Form::submit('save',['class'=>'form-group btn btn-primary pull-right'])!!}</h4>
        </div>

      </div>

      <!-- check whether success or not -->

      @if(Session::has('success'))
        <div class="alert alert-success alert-dismissable">
          <i class="fa  fa-check-circle"></i>
          <b>Success!</b>
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          {{Session::get('success')}}
        </div>
        @endif
          <!-- failure message -->
        @if(Session::has('fails'))
          <div class="alert alert-danger alert-dismissable">
            <i class="fa fa-ban"></i>
            <b>Alert!</b> Failed.
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{Session::get('fails')}}
          </div>
          @endif

            <!-- Name text form Required -->
          <div class="box-body table-responsive" style="overflow:hidden;">

            <div class="row">

              <div class="col-md-10 form-group {{ $errors->has('faq') ? 'has-error' : '' }}">
                {!! Form::label('faq','Description') !!}
                {!! $errors->first('faq', '<span class="help-block">:message</span>') !!}

                {!! Form::textarea('faq',null,['class' => 'form-control','size' => '30x5','id'=>'faq']) !!}

              </div>
              <script language="JavaScript" type="text/javascript">
                CKEDITOR.replace('faq',
                  {
                    filebrowserUploadUrl: '/uploader/upload.php',

                  });

                CKEDITOR.replace('faq', {toolbar: 'MyToolbar'});
              </script>


            </div>
          </div>
    </div>
  </div>
  @stop
</div><!-- /.box -->
@section('FooterInclude')

@stop
@stop
  <!-- /content -->

@stop