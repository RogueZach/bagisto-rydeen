<?php

namespace Rydeen\Dealer\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DealerContactDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('rydeen_dealer_contacts')
            ->leftJoin('customers', 'rydeen_dealer_contacts.customer_id', '=', 'customers.id')
            ->leftJoin('orders', 'orders.dealer_contact_id', '=', 'rydeen_dealer_contacts.id')
            ->select(
                'rydeen_dealer_contacts.id',
                DB::raw("CONCAT(rydeen_dealer_contacts.first_name, ' ', rydeen_dealer_contacts.last_name) as contact_name"),
                'rydeen_dealer_contacts.email as contact_email',
                'rydeen_dealer_contacts.phone as contact_phone',
                DB::raw("CONCAT(customers.first_name, ' ', customers.last_name) as dealer_name"),
                'rydeen_dealer_contacts.is_active',
                'rydeen_dealer_contacts.created_at',
                DB::raw('COUNT(orders.id) as order_count')
            )
            ->groupBy(
                'rydeen_dealer_contacts.id',
                'rydeen_dealer_contacts.first_name',
                'rydeen_dealer_contacts.last_name',
                'rydeen_dealer_contacts.email',
                'rydeen_dealer_contacts.phone',
                'rydeen_dealer_contacts.is_active',
                'rydeen_dealer_contacts.created_at',
                'customers.first_name',
                'customers.last_name'
            );

        $this->setQueryBuilder($queryBuilder);
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => 'ID',
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'contact_name',
            'label'      => 'Contact Name',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'contact_email',
            'label'      => 'Email',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'contact_phone',
            'label'      => 'Phone',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'dealer_name',
            'label'      => 'Dealer',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'order_count',
            'label'      => 'Orders',
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'is_active',
            'label'      => 'Active',
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->is_active
                ? '<span class="badge badge-md badge-success">Active</span>'
                : '<span class="badge badge-md badge-danger">Inactive</span>',
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => 'Created',
            'type'       => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon'   => 'icon-eye',
            'title'  => 'View',
            'method' => 'GET',
            'url'    => fn ($row) => route('admin.rydeen.contacts.view', $row->id),
        ]);
    }
}
