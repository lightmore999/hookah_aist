<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0">Способы оплаты</h2>
        <p class="text-muted mb-0 small">Управление способами оплаты</p>
    </div>
    
    <div>
        <button type="button" 
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#createPaymentMethodModal">
            <i class="bi bi-plus-circle me-1"></i> Добавить способ оплаты
        </button>
    </div>
</div>

@if(session('success_payment'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success_payment') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($paymentMethods->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-credit-card display-1 text-muted"></i>
                <p class="mt-3 text-muted">Нет способов оплаты. Добавьте первый!</p>
                <button type="button" 
                        class="btn btn-primary mt-2"
                        data-bs-toggle="modal"
                        data-bs-target="#createPaymentMethodModal">
                    <i class="bi bi-plus-circle me-1"></i> Добавить способ оплаты
                </button>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Название</th>
                            <th>Дата создания</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentMethods as $method)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $method->Name }}</strong>
                            </td>
                            <td>{{ $method->created_at->format('d.m.Y H:i') }}</td>
                            <td class="text-end">
                                <button type="button" 
                                        class="btn btn-warning btn-sm edit-payment-method-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editPaymentMethodModal"
                                        data-id="{{ $method->IDPaymentMethod }}"
                                        data-name="{{ $method->Name }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <form action="{{ route('payment-methods.destroy', $method) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Вы уверены, что хотите удалить этот способ оплаты?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>