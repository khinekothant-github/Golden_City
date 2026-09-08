<?php

namespace App\Http\Controllers;

use App\Enums\CustomerType;
use App\Enums\PurchasePurpose;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerRepository $customers) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString() ?: null;

        return CustomerResource::collection($this->customers->paginate($search));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:255', Rule::unique('customers', 'contact_number')->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255'],
            'type' => ['required', Rule::enum(CustomerType::class)],
            'appointment_time' => ['nullable', 'date'],
            'purchase_purpose' => ['nullable', Rule::enum(PurchasePurpose::class)],
            'sale_person_ids' => ['nullable', 'array'],
            'sale_person_ids.*' => ['integer', Rule::exists('users', 'id')],
            'preferred_unit_ids' => ['nullable', 'array'],
            'preferred_unit_ids.*' => ['integer', Rule::exists('units', 'id')->whereNull('deleted_at')],
        ]);

        return (new CustomerResource($this->customers->createWithBooking($data)))->response()->setStatusCode(201);
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($this->customers->find($customer->id));
    }

    public function update(Request $request, Customer $customer): CustomerResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_number' => ['sometimes', 'string', 'max:255', Rule::unique('customers', 'contact_number')->ignore($customer->id)->whereNull('deleted_at')],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'type' => ['sometimes', Rule::enum(CustomerType::class)],
            'appointment_time' => ['sometimes', 'nullable', 'date'],
            'purchase_purpose' => ['sometimes', 'nullable', Rule::enum(PurchasePurpose::class)],
            'sale_person_ids' => ['sometimes', 'nullable', 'array'],
            'sale_person_ids.*' => ['integer', Rule::exists('users', 'id')],
        ]);

        return new CustomerResource($this->customers->update($customer, $data));
    }

    public function destroy(Customer $customer): JsonResponse
    {
        if ($this->customers->hasSales($customer)) {
            return response()->json(['message' => 'Customer has sales history and cannot be deleted.'], 422);
        }

        $this->customers->delete($customer);

        return response()->json(['message' => 'Customer deleted.']);
    }
}
