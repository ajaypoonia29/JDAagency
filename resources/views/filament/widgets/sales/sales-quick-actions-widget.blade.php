<x-filament-widgets::widget>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;">

        <a
            href="{{ route('filament.admin.resources.leads.create') }}"
            style="display:block;padding:24px;border:2px solid red;border-radius:16px;background:#fff;"
        >
            <h3>New Lead</h3>
            <p>Add a new sales lead</p>
        </a>

        <a
            href="{{ route('filament.admin.resources.customers.create') }}"
            style="display:block;padding:24px;border:2px solid green;border-radius:16px;background:#fff;"
        >
            <h3>New Customer</h3>
            <p>Register a customer</p>
        </a>

        <a
            href="{{ route('filament.admin.resources.quotations.create') }}"
            style="display:block;padding:24px;border:2px solid orange;border-radius:16px;background:#fff;"
        >
            <h3>New Quotation</h3>
            <p>Create a quotation</p>
        </a>

        <a
            href="{{ route('filament.admin.resources.meetings.create') }}"
            style="display:block;padding:24px;border:2px solid blue;border-radius:16px;background:#fff;"
        >
            <h3>Schedule Meeting</h3>
            <p>Book a client meeting</p>
        </a>

    </div>

</x-filament-widgets::widget>