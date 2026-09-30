{{-- Permission grid of a role: one row per service, one column per action. $checked = permission names already granted. --}}
@php $checked = old('permissions', $checked ?? []); @endphp
<div class="sa-form-section" style="grid-template-columns: 1fr;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="sa-form-section-intro">
            <span class="sa-tile"><i class="bi bi-ui-checks-grid"></i></span>
            <div>
                <h3>Permissions</h3>
                <p>Cochez ce que ce rôle peut faire. « Read » donne accès à la page, les autres actions aux boutons.</p>
            </div>
        </div>
        <button type="button" class="btn btn-soft btn-sm" id="toggle-all-perms"><i class="bi bi-check2-all"></i> Tout cocher</button>
    </div>

    <div class="sa-card overflow-hidden" style="box-shadow: none;">
        <div class="sa-table-wrap">
            <table class="table sa-table sa-perm-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        @foreach ($actions as $action)
                            <th>{{ ucfirst($action) }}</th>
                        @endforeach
                        <th>Ligne</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($services as $service)
                        <tr>
                            <td class="sa-strong">{{ ucfirst($service) }}</td>
                            @foreach ($actions as $action)
                                @php $perm = strtolower($service) . '.' . $action; @endphp
                                <td>
                                    <input class="form-check-input perm-checkbox" type="checkbox" name="permissions[]" value="{{ $perm }}"
                                           aria-label="{{ $service }} {{ $action }}" @checked(in_array($perm, $checked, true))>
                                </td>
                            @endforeach
                            <td><button type="button" class="sa-icon-btn perm-row" title="Toute la ligne"><i class="bi bi-arrow-left-right"></i></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- rights that are not a whole section (App\Support\Droits::SPECIAUX) --}}
    <div class="row g-2">
        @foreach ($speciaux ?? [] as $perm => [$label, $explication])
            <div class="col-md-4">
                <label class="sa-kpi-mini h-100 justify-content-start align-items-start gap-2" style="cursor: pointer;">
                    <input class="form-check-input perm-checkbox mt-1 flex-shrink-0" type="checkbox" name="permissions[]" value="{{ $perm }}" @checked(in_array($perm, $checked, true))>
                    <span>
                        <span class="sa-strong d-block">{{ $label }}</span>
                        <span class="sa-sub">{{ $explication }}</span>
                    </span>
                </label>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var boxes = function (scope) { return Array.prototype.slice.call(scope.querySelectorAll('.perm-checkbox')); };
        var toggle = function (list) {
            var all = list.every(function (cb) { return cb.checked; });
            list.forEach(function (cb) { cb.checked = !all; });
        };
        document.getElementById('toggle-all-perms').addEventListener('click', function () { toggle(boxes(document)); });
        document.querySelectorAll('.perm-row').forEach(function (btn) {
            btn.addEventListener('click', function () { toggle(boxes(btn.closest('tr'))); });
        });
    })();
</script>
@endpush
