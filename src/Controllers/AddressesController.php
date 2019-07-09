<?php

namespace Varbox\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Varbox\Contracts\UserModelContract;
use Varbox\Traits\CanCrud;
use Varbox\Contracts\AddressModelContract;
use Varbox\Contracts\CityModelContract;
use Varbox\Contracts\CountryModelContract;
use Varbox\Contracts\StateModelContract;
use Varbox\Filters\AddressFilter;
use Varbox\Requests\AddressRequest;
use Varbox\Sorts\AddressSort;

class AddressesController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use CanCrud;

    /**
     * @var AddressModelContract
     */
    protected $model;

    /**
     * @var CountryModelContract
     */
    protected $country;

    /**
     * @var StateModelContract
     */
    protected $state;

    /**
     * @var CityModelContract
     */
    protected $city;

    /**
     * @param AddressModelContract $model
     * @param CountryModelContract $country
     * @param StateModelContract $state
     * @param CityModelContract $city
     */
    public function __construct(AddressModelContract $model, CountryModelContract $country, StateModelContract $state, CityModelContract $city)
    {

        $this->model = $model;
        $this->country = $country;
        $this->state = $state;
        $this->city = $city;
    }

    /**
     * @param Request $request
     * @param AddressFilter $filter
     * @param AddressSort $sort
     * @param UserModelContract $user
     * @return \Illuminate\View\View
     * @throws \Exception
     */
    public function index(Request $request, AddressFilter $filter, AddressSort $sort, UserModelContract $user)
    {
        $this->displayOwnerUserMessage($user);

        return $this->_index(function () use ($request, $filter, $sort, $user) {
            $this->items = $this->model->ofUser($user)
                ->filtered($request->all(), $filter)
                ->sorted($request->all(), $sort)
                ->paginate(config('varbox.crud.per_page', 10));

            $this->title = 'Addresses';
            $this->view = view('varbox::admin.addresses.index');
            $this->vars = [
                'user' => $user,
                'countries' => $this->country->alphabetically()->get(),
            ];
        });
    }

    /**
     * @param UserModelContract $user
     * @return \Illuminate\View\View
     * @throws \Exception
     */
    public function create(UserModelContract $user)
    {
        $this->displayOwnerUserMessage($user);

        return $this->_create(function () use ($user) {
            $this->title = 'Add Address';
            $this->view = view('varbox::admin.addresses.add');
            $this->vars = [
                'user' => $user,
                'countries' => $this->country->alphabetically()->get(),
            ];
        });
    }

    /**
     * @param Request $request
     * @param UserModelContract $user
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function store(Request $request, UserModelContract $user)
    {
        app(config('varbox.bindings.form_requests.address_form_request', AddressRequest::class));

        return $this->_store(function () use ($request, $user) {
            $this->item = $this->model->create($request->all());
            $this->redirect = redirect()->route('admin.addresses.index', $user->getKey());
        }, $request);
    }

    /**
     * @param UserModelContract $user
     * @param AddressModelContract $address
     * @return \Illuminate\View\View
     * @throws \Exception
     */
    public function edit(UserModelContract $user, AddressModelContract $address)
    {
        $this->displayOwnerUserMessage($user);

        return $this->_edit(function () use ($user, $address) {
            $this->item = $address;
            $this->title = 'Edit Address';
            $this->view = view('varbox::admin.addresses.edit');
            $this->vars = [
                'user' => $user,
                'countries' => $this->country->alphabetically()->get(),
                'states' => $this->state->alphabetically()
                    ->fromCountry($address->country_id)
                    ->get(),
                'cities' => $this->city->alphabetically()
                    ->fromCountry($address->country_id)
                    ->fromState($address->state_id)
                    ->get(),
            ];
        });
    }

    /**
     * @param Request $request
     * @param AddressModelContract $address
     * @param UserModelContract $user
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function update(Request $request, UserModelContract $user, AddressModelContract $address)
    {
        app(config('varbox.bindings.form_requests.address_form_request', AddressRequest::class));

        return $this->_update(function () use ($request, $user, $address) {
            $this->item = $address;
            $this->redirect = redirect()->route('admin.addresses.index', $user->getKey());

            $this->item->update($request->all());
        }, $request);
    }

    /**
     * @param AddressModelContract $address
     * @param UserModelContract $user
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(UserModelContract $user, AddressModelContract $address)
    {
        return $this->_destroy(function () use ($user, $address) {
            $this->item = $address;
            $this->redirect = redirect()->route('admin.addresses.index', $user->getKey());

            $this->item->delete();
        });
    }

    /**
     * @param UserModelContract $user
     * @return void
     */
    protected function displayOwnerUserMessage(UserModelContract $user)
    {
        flash()->info(
            'You are viewing the addresses for user: ' .
            '<strong><a href="' . route('admin.users.edit', $user->id) . '" style="color: #24587e;">' . $user->email . '</a></strong>'
        );
    }
}
