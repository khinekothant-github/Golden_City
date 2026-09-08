<?php

namespace App\Http\Controllers;

use App\Enums\PurchasePurpose;
use App\Enums\Sentiment;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerVisitController extends Controller
{
    public function __construct(private readonly CustomerRepository $customers) {}

    public function store(Request $request, Customer $customer): CustomerResource
    {
        $data = $request->validate([
            'purchase_purpose' => ['nullable', Rule::enum(PurchasePurpose::class)],
            'feedbacks' => ['nullable', 'array'],
            'feedbacks.*.unit_id' => ['required', 'integer', Rule::exists('units', 'id')->whereNull('deleted_at')],
            'feedbacks.*.sentiment' => ['required', Rule::in([Sentiment::Liked->value, Sentiment::Disliked->value])],
            'feedbacks.*.comment' => ['nullable', 'string'],
            'source' => ['nullable', 'array'],
            'source.category' => ['required_with:source', 'string', 'max:255'],
            'source.sub_category' => ['nullable', 'string', 'max:255'],
            'source.agency_id' => ['nullable', 'integer', Rule::exists('agencies', 'id')->whereNull('deleted_at')],
            'source.event_id' => ['nullable', 'integer', Rule::exists('events', 'id')],
        ]);

        return new CustomerResource($this->customers->recordVisit($customer, $data));
    }
}
