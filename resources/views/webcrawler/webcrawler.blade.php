@extends('layouts.app')

@section('content')
<div class="container">
	<div class="row">
		<form>
			<div class="form-group" method="GET" action="{{ route('crawler.index') }}">
				@csrf
				<label for="name">Company name</label>
				<input type="text" class="form-control" name="name" id="name"  placeholder="name">
			</div>
			<button type="submit" class="btn btn-primary">Submit</button>
		</form>

	</div>
	{{--<input type="hidden" id="crawlerID"  value="{{ route('crawler.store')}}"/>--}}
	<div class="row"> 
		<div class="col-md-8">
			@if(isset($companiesList))
				@foreach ($companiesList as $company)
				<ul class="list-group">
					<li class="list-group-item">{!! $company['company_name'] !!}({!! $company['organization_number'] !!}) </li>
					<li class="list-group-item" data-test="{{}}">Test value</li>
				</ul>
				@endforeach

			@endif

		</div>
	</div>
</div>


@endsection