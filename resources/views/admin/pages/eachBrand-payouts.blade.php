@extends('admin.layout.layout')

@section('title', 'Admin | Brand Payments')

@section('admin-content')



    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <h1 class="fw-bold" style="color: #003C51;">Brand Payments Overview</h1>
                </div>
            </div>
        </div>
        <hr>
        <div class="row my-5 fullInfo">
            <div class="col-lg-12">
                <div class="row my-5 fullInfo">
                    <div class="col-lg-12">
                        @foreach ($brandData as $data)
                            <h4>{{ $data['brand']->brand_name }}</h4>
                            @if (count($data['payouts']) > 0)
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Payout ID</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date Created</th>
                                            <th>Balance Txn</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data['payouts'] as $payout)
                                            <tr>
                                                <td>{{ $payout->id }}</td>
                                                <td>{{ number_format($payout->amount / 100, 2) }}
                                                    {{ strtoupper($payout->currency) }}</td>
                                                <td>{{ $payout->status }}</td>
                                                <td>{{ \Carbon\Carbon::createFromTimestamp($payout->created)->toDateTimeString() }}
                                                </td>
                                                <td>
                                                    @if (isset($payout->balance_details))
                                                        {{ $payout->balance_details->id }}
                                                        ({{ number_format($payout->balance_details->amount / 100, 2) }})
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p>No payouts yet.</p>
                            @endif
                        @endforeach

                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection
