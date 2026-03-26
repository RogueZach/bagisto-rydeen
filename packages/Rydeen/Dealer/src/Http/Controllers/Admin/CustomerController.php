<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Webkul\B2BSuite\Http\Controllers\Admin\CustomerController as B2BCustomerController;

class CustomerController extends B2BCustomerController
{
    /**
     * Fix B2B Suite bug: missing $channels in customer index view.
     */
    public function index()
    {
        if (request()->ajax()) {
            return parent::index();
        }

        $channels = core()->getAllChannels();

        $groups = $this->customerGroupRepository->findWhere([['code', '<>', 'guest']]);

        return view('admin::customers.customers.index', compact('channels', 'groups'));
    }
}
