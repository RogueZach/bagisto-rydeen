<x-admin::layouts>
    <x-slot:title>
        Dealer Contacts
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-6">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Dealer Contacts
        </p>
    </div>

    <x-admin::datagrid :src="route('admin.rydeen.contacts.index')" />
</x-admin::layouts>
