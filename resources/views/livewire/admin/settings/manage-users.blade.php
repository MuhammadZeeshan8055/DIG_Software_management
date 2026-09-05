<div class="manage-users-page" @users-panel-opened.window="$wire.$refresh()">
    @if (! empty($denied))
        <p class="manage-users-empty">You do not have access to manage users.</p>
    @else

    @if ($successMessage)
        <x-admin-alert type="success" wire-property="successMessage">
            {{ $successMessage }}
        </x-admin-alert>
    @endif

    <div class="data-panel">
        <div class="data-panel__head">
            <div class="manage-users-head">
                <h3 class="data-panel__title">All Users</h3>
                <span class="add-account__count">{{ $users->count() }} total</span>
            </div>
            <button type="button" class="hero-btn hero-btn--primary" wire:click="openCreateModal">
                Add User
            </button>
        </div>

        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="method-badge method-badge--{{ $user->role }}">
                                    {{ str_replace('_', ' ', $user->role) }}
                                </span>
                            </td>
                            <td class="manage-users-actions">
                                <button
                                    type="button"
                                    class="payment-actions__btn"
                                    wire:click="editUser({{ $user->id }})"
                                >Edit</button>
                                @if ($user->id !== auth()->id())
                                    <button
                                        type="button"
                                        class="payment-actions__btn payment-actions__btn--danger"
                                        wire:click="deleteUser({{ $user->id }})"
                                        wire:confirm="Delete this user?"
                                    >Delete</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="receiving-accounts-table__empty">
                                No users yet. Click Add User to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{--
      Modal is rendered here (NO x-teleport).
      Component is mounted outside admin-shell so fixed + high z-index covers header/sidebar.
    --}}
    @if ($showModal)
        <div class="mu-modal" wire:keydown.escape.window="closeModal">
            <button type="button" class="mu-modal__backdrop" wire:click="closeModal" aria-label="Close"></button>

            <div class="mu-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mu-modal-title">
                <div class="mu-modal__head">
                    <h3 id="mu-modal-title" class="mu-modal__title">
                        {{ $editingId ? 'Edit User' : 'Add User' }}
                    </h3>
                    <button type="button" class="mu-modal__close" wire:click="closeModal" aria-label="Close">&times;</button>
                </div>

                <form wire:submit="saveUser" class="manage-users-form mu-modal__form">
                    <div class="manage-users-form__body">
                        <div class="manage-users-form__grid">
                            <div class="mu-field">
                                <label class="mu-field__label" for="mu-name">Name</label>
                                <input id="mu-name" type="text" wire:model="name" class="mu-field__input" placeholder="Full name">
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="mu-email">Email</label>
                                <input id="mu-email" type="email" wire:model="email" class="mu-field__input" placeholder="user@example.com">
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="mu-password">
                                    {{ $editingId ? 'New password (optional)' : 'Password' }}
                                </label>
                                <input
                                    id="mu-password"
                                    type="password"
                                    wire:model="password"
                                    class="mu-field__input"
                                    placeholder="{{ $editingId ? 'Leave blank to keep current' : 'Min 8 characters' }}"
                                    autocomplete="new-password"
                                >
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="mu-role">Role</label>
                                <select id="mu-role" wire:model.live="role" class="mu-field__input mu-field__input--select">
                                    @foreach ($roleOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @if ($role === 'staff')
                            <div class="mu-access">
                                <div class="mu-access__head">
                                    <p class="mu-access__title">Feature access</p>
                                    <p class="mu-access__hint">Pick modules this staff user can use</p>
                                </div>

                                <label class="mu-access__all">
                                    <input type="checkbox" wire:model.live="allowAllAccess">
                                    <span class="mu-access__all-text">
                                        <strong>Allow all access</strong>
                                        <small>Manage every module and feature</small>
                                    </span>
                                </label>

                                @unless ($allowAllAccess)
                                    <div class="mu-access__list">
                                        @foreach ($assignableModules as $module)
                                            @php $key = $module['key']; @endphp
                                            <div class="mu-access__row" wire:key="mod-{{ $key }}">
                                                <label class="mu-access__check">
                                                    <input type="checkbox" wire:model.live="moduleEnabled.{{ $key }}">
                                                    <span>{{ $module['title'] }}</span>
                                                </label>

                                                <select
                                                    wire:model="moduleLevel.{{ $key }}"
                                                    class="mu-access__level {{ empty($moduleEnabled[$key]) ? 'mu-access__level--off' : '' }}"
                                                    @disabled(empty($moduleEnabled[$key]))
                                                >
                                                    <option value="view">View only</option>
                                                    <option value="manage">Manage</option>
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                @endunless
                            </div>
                        @else
                            <p class="mu-access__admin-note">
                                Admin and Super Admin get full access automatically.
                            </p>
                        @endif

                        <x-validation-errors class="import-ticket__validation-errors" />
                    </div>

                    <div class="mu-modal__footer">
                        <button type="button" class="payment-actions__btn" wire:click="closeModal">Cancel</button>
                        <button type="submit" class="hero-btn hero-btn--primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveUser">
                                {{ $editingId ? 'Update User' : 'Create User' }}
                            </span>
                            <span wire:loading wire:target="saveUser">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    @endif
</div>
