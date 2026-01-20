@if($historyData->count() > 0)
    @foreach($historyData as $item)
        <tr class="hover:bg-gray-50">
            <td class="border-0">
                <!-- ✅ PERBAIKAN: Gunakan $item->id bukan $item->hu_number -->
                <input type="checkbox" class="form-check-input row-checkbox"
                       value="{{ $item->id }}" data-hu="{{ $item->hu_number }}">
            </td>
            <td class="border-0">
                <span class="fw-bold text-primary">{{ $item->hu_number }}</span>
            </td>
            <td class="border-0">
                <span class="material-number">
                    {{ preg_match('/^\d+$/', $item->material) ? ltrim($item->material, '0') : $item->material }}
                </span>
            </td>
            <td class="border-0 text-gray-600 material-description">
                {{ $item->material_description ?: '-' }}
            </td>
            <td class="border-0 text-gray-600">{{ $item->batch ?: '-' }}</td>
            <td class="border-0 text-end">
                <span class="badge bg-success bg-opacity-10 text-success fs-6">
                    {{ number_format((float)($item->quantity ?? 0), 0, ',', '.') }}
                </span>
            </td>
            <td class="border-0 text-gray-600">{{ $item->unit == 'ST' ? 'PC' : ($item->unit ?: '-') }}</td>
            <td class="border-0">
                <span class="sales-document">{{ $item->sales_document ?: '-' }}</span>
            </td>
            <td class="border-0 text-gray-600">{{ $item->storage_location ?: '-' }}</td>
            <td class="border-0">
                @if($item->scenario_type == 'single')
                    <span class="badge bg-primary text-white">Skenario 1</span>
                @elseif($item->scenario_type == 'single-multi')
                    <span class="badge bg-success text-white">Skenario 2</span>
                @elseif($item->scenario_type == 'multiple')
                    <span class="badge bg-purple-600 text-white">Skenario 3</span>
                @else
                    <span class="badge bg-secondary text-white">{{ $item->scenario_type ?: '-' }}</span>
                @endif
            </td>
            <td class="border-0 text-gray-600">
                <!-- ✅ KOLOM BARU: CREATED BY -->
                <span class="created-by" title="{{ $item->created_by ?: 'System' }}">
                    {{ $item->created_by ?: 'System' }}
                </span>
            </td>
            <td class="border-0 text-gray-600">
                @php
                    try {
                        $createdAt = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') : '-';
                    } catch (Exception $e) {
                        $createdAt = '-';
                    }
                @endphp
                {{ $createdAt }}
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="12" class="text-center py-4 text-muted">
            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
            Tidak ada data history HU
        </td>
    </tr>
@endif