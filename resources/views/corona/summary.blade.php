@extends('layouts.app')

@section('content')
<div class="container">
		<div class="col-md-6">
			@if(isset($data))	
				@foreach ($data as $key => $value)
					<ul class="list-group">
						<li class="list-group-item">{!! $key !!} {!! $value!!} </li>
					</ul>
				@endforeach
			@endif

		</div>	
	</div>
</div>
@endsection