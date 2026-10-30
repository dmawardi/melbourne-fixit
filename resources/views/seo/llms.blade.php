# {!! $business['name'] !!}

> {!! $business['description'] !!}

{!! $business['name'] !!} takes requests for home maintenance, repairs and renovations in {!! $business['city'] !!}, {!! $business['region'] !!}, Australia. Customers describe the job on the website, and the team reviews it and replies with a quote. Only Victorian addresses (postcodes starting with 3) are serviced.

## Pages

- [Home]({!! route('home') !!}): services, service areas and frequently asked questions
- [Request a quote]({!! route('job-requests.create') !!}): the form for submitting a job; no account is needed

## Services

@foreach ($services as $service)
- {!! $service['label'] !!}: {!! $service['description'] !!}
@endforeach

## Service areas

@foreach ($business['service_areas'] as $area)
- {!! $area !!}
@endforeach

## Frequently asked questions

@foreach ($faqs as $faq)
### {!! $faq['question'] !!}

{!! $faq['answer'] !!}

@endforeach
@if ($business['phone'] || $business['email'])
## Contact

@if ($business['phone'])
- Phone: {!! $business['phone'] !!}
@endif
@if ($business['email'])
- Email: {!! $business['email'] !!}
@endif
@endif
