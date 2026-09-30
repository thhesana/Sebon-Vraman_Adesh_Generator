{{-- List table inside a card: pass the column titles as :headings, the rows go in the slot. --}}
@props(['headings' => []])

<div class="table-card">
    <div class="table-responsive">
        <table {{ $attributes->class(['table', 'table-striped', 'table-hover', 'align-middle']) }}>
            <thead>
                <tr>
                    @foreach ($headings as $heading)
                        <th scope="col">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
