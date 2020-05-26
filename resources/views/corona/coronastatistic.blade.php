@extends('layouts.app')

@section('content')
<div class="container">
	<div class="row">
		<div class="col-md-6">
			<form>
				<div class="form-group" method="GET" action="{{ route('corona.index') }}">
					@csrf
					<label for="name">Search Global COVID-19 case</label>
					<input type="text" class="form-control" name="name" id="name"  placeholder="country-name">
				</div>
				<button type="submit" class="btn btn-primary">Submit</button>
			</form>
		</div>
	</div>
</div>


@endsection