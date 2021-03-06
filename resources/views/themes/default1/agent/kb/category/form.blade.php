<div class="box-body">
  @if(Session::has('success'))
    <div class="alert alert-success alert-dismissable">
      <i class="fa  fa-check-circle"></i>
      <b>Success</b>
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
      {{Session::get('success')}}
    </div>
    @endif
      <!-- failure message -->
    @if(Session::has('fails'))
      <div class="alert alert-danger alert-dismissable">
        <i class="fa fa-ban"></i>
        <b>Fail!</b>
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        {{Session::get('fails')}}
      </div>
    @endif

    <div class="row">

      <div class="col-xs-4 form-group {{ $errors->has('name') ? 'has-error' : '' }}">

        {!! Form::label('name',Lang::get('lang.name')) !!}
        {!! $errors->first('name', '<span class="help-block">:message</span>') !!}
        {!! Form::text('name',null,['class' => 'form-control']) !!}

      </div>

      {{--  --}}

      <div class="col-xs-4 form-group {{ $errors->has('status') ? 'has-error' : '' }}">

        {!! Form::label('status',Lang::get('lang.status')) !!}
        {!! $errors->first('status', '<span class="help-block">:message</span>') !!}
        <div class="row">
          <div class="col-xs-3">
            {!! Form::radio('status','1',true) !!}{!! Lang::get('lang.active') !!}
          </div>
          <div class="col-xs-3">
            {!! Form::radio('status','0',null) !!}{!! Lang::get('lang.inactive') !!}
          </div>
        </div>
      </div>

    </div>
    <div class="form-group {{ $errors->has('description') ? 'has-error' : '' }}">
      {!! Form::label('description',Lang::get('lang.description')) !!}
      {!! $errors->first('description', '<span class="help-block">:message</span>') !!}

      {!! Form::textarea('description',null,['class' => 'form-control','size' => '50x10','id'=>'myNicEditor','placeholder'=>Lang::get('lang.enter_the_description')]) !!}
    </div>
</div>

