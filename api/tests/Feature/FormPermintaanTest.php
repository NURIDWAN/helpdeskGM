<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\FormPermintaan;
use App\Models\FormPermintaanItem;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// =====================================================
// LIST FORM PERMINTAAN TESTS
// =====================================================

test('admin can list form permintaan', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/form-permintaan?row_per_page=10')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]
        ]);
});

test('staff can list form permintaan', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    actingAs($staff)
        ->getJson('/api/v1/form-permintaan?row_per_page=10')
        ->assertOk();
});

test('user can list form permintaan', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('user');

    actingAs($user)
        ->getJson('/api/v1/form-permintaan?row_per_page=10')
        ->assertOk();
});

test('unauthenticated user cannot list form permintaan', function () {
    getJson('/api/v1/form-permintaan?row_per_page=10')
        ->assertStatus(401);
});

// =====================================================
// CREATE FORM PERMINTAAN TESTS
// =====================================================

test('admin can create form permintaan', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->assignRole('admin');

    $data = [
        'branch_id' => $branch->id,
        'priority' => 'high',
        'request_type' => 'pembelian_produk_baru',
        'reason' => 'Kebutuhan operasional baru',
        'items' => [
            [
                'product_description' => 'AC Split 1 PK',
                'quantity' => 2,
                'uom' => 'unit',
                'notes' => 'Untuk ruangan meeting',
            ],
        ],
    ];

    actingAs($admin)
        ->postJson('/api/v1/form-permintaan', $data)
        ->assertStatus(201)
        ->assertJsonPath('success', true);
});

test('form permintaan requires items', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->assignRole('admin');

    actingAs($admin)
        ->postJson('/api/v1/form-permintaan', [
            'priority' => 'high',
            'request_type' => 'pembelian_produk_baru',
            'reason' => 'Alasan',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

test('form permintaan requires priority', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->assignRole('admin');

    actingAs($admin)
        ->postJson('/api/v1/form-permintaan', [
            'request_type' => 'pembelian_produk_baru',
            'reason' => 'Alasan',
            'items' => [
                ['product_description' => 'Item', 'quantity' => 1, 'uom' => 'pcs'],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['priority']);
});

test('form permintaan requires request_type', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->assignRole('admin');

    actingAs($admin)
        ->postJson('/api/v1/form-permintaan', [
            'priority' => 'medium',
            'reason' => 'Alasan',
            'items' => [
                ['product_description' => 'Item', 'quantity' => 1, 'uom' => 'pcs'],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['request_type']);
});

test('fa_number required for penggantian_produk_lama', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->assignRole('admin');

    actingAs($admin)
        ->postJson('/api/v1/form-permintaan', [
            'priority' => 'medium',
            'request_type' => 'penggantian_produk_lama',
            'items' => [
                ['product_description' => 'Item', 'quantity' => 1, 'uom' => 'pcs'],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['fa_number']);
});

test('user without form-permintaan-create permission cannot create', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('user'); // user role does not have form-permintaan-create

    actingAs($user)
        ->postJson('/api/v1/form-permintaan', [
            'priority' => 'high',
            'request_type' => 'pembelian_produk_baru',
            'reason' => 'Alasan',
            'items' => [
                ['product_description' => 'Item', 'quantity' => 1, 'uom' => 'pcs'],
            ],
        ])
        ->assertStatus(403);
});

// =====================================================
// VIEW FORM PERMINTAAN TESTS
// =====================================================

test('admin can view single form permintaan', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->assignRole('admin');

    $form = FormPermintaan::create([
        'user_id' => $admin->id,
        'branch_id' => $branch->id,
        'request_number' => 'FP-' . now()->format('Ymd') . '-001',
        'date' => now()->format('Y-m-d'),
        'priority' => 'high',
        'request_type' => 'pembelian_produk_baru',
        'reason' => 'Test reason',
        'status' => 'pending',
    ]);

    actingAs($admin)
        ->getJson("/api/v1/form-permintaan/{$form->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $form->id);
});

test('returns 404 for non-existent form permintaan', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/form-permintaan/99999')
        ->assertStatus(404);
});

// =====================================================
// UPDATE STATUS TESTS
// =====================================================

test('staff can update form permintaan status', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $form = FormPermintaan::create([
        'user_id' => $staff->id,
        'branch_id' => $branch->id,
        'request_number' => 'FP-' . now()->format('Ymd') . '-002',
        'date' => now()->format('Y-m-d'),
        'priority' => 'medium',
        'request_type' => 'servis',
        'fa_number' => 'FA-001',
        'status' => 'pending',
    ]);

    actingAs($staff)
        ->putJson("/api/v1/form-permintaan/{$form->id}/status", [
            'status' => 'approved',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('confirm sets confirmed_by and confirmed_at', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $form = FormPermintaan::create([
        'user_id' => $staff->id,
        'branch_id' => $branch->id,
        'request_number' => 'FP-' . now()->format('Ymd') . '-003',
        'date' => now()->format('Y-m-d'),
        'priority' => 'low',
        'request_type' => 'jasa',
        'status' => 'pending',
    ]);

    actingAs($staff)
        ->putJson("/api/v1/form-permintaan/{$form->id}/confirm")
        ->assertOk()
        ->assertJsonPath('success', true);

    $form->refresh();
    expect($form->confirmed_by)->toBe($staff->id);
    expect($form->confirmed_at)->not->toBeNull();
});

// =====================================================
// REVIEW FORM PERMINTAAN TESTS
// =====================================================

test('reviewer can review pending form permintaan', function () {
    $branch = Branch::factory()->create();
    $reviewer = User::factory()->create(['branch_id' => $branch->id]);
    $reviewer->assignRole('reviewer-permintaan');

    $form = FormPermintaan::create([
        'user_id' => $reviewer->id,
        'branch_id' => $branch->id,
        'request_number' => 'FP-' . now()->format('Ymd') . '-004',
        'date' => now()->format('Y-m-d'),
        'priority' => 'medium',
        'request_type' => 'pembelian_produk_baru',
        'reason' => 'Test',
        'status' => 'pending',
    ]);

    actingAs($reviewer)
        ->putJson("/api/v1/form-permintaan/{$form->id}/review")
        ->assertOk()
        ->assertJsonPath('success', true);

    $form->refresh();
    expect($form->status)->toBe('reviewed');
    expect($form->reviewed_by)->toBe($reviewer->id);
});

test('cannot review non-pending form permintaan', function () {
    $branch = Branch::factory()->create();
    $reviewer = User::factory()->create(['branch_id' => $branch->id]);
    $reviewer->assignRole('reviewer-permintaan');

    $form = FormPermintaan::create([
        'user_id' => $reviewer->id,
        'branch_id' => $branch->id,
        'request_number' => 'FP-' . now()->format('Ymd') . '-005',
        'date' => now()->format('Y-m-d'),
        'priority' => 'medium',
        'request_type' => 'jasa',
        'status' => 'approved',
    ]);

    actingAs($reviewer)
        ->putJson("/api/v1/form-permintaan/{$form->id}/review")
        ->assertStatus(422);
});

// =====================================================
// REJECT FORM PERMINTAAN TESTS
// =====================================================

test('staff can reject pending form permintaan', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $form = FormPermintaan::create([
        'user_id' => $staff->id,
        'branch_id' => $branch->id,
        'request_number' => 'FP-' . now()->format('Ymd') . '-006',
        'date' => now()->format('Y-m-d'),
        'priority' => 'high',
        'request_type' => 'penggantian_part',
        'fa_number' => 'FA-002',
        'status' => 'pending',
    ]);

    actingAs($staff)
        ->putJson("/api/v1/form-permintaan/{$form->id}/reject", [
            'reason' => 'Budget tidak tersedia',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $form->refresh();
    expect($form->status)->toBe('rejected');
    expect($form->rejected_by)->toBe($staff->id);
    expect($form->rejection_reason)->toBe('Budget tidak tersedia');
});

test('cannot reject approved form permintaan', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $form = FormPermintaan::create([
        'user_id' => $staff->id,
        'branch_id' => $branch->id,
        'request_number' => 'FP-' . now()->format('Ymd') . '-007',
        'date' => now()->format('Y-m-d'),
        'priority' => 'medium',
        'request_type' => 'jasa',
        'status' => 'approved',
    ]);

    actingAs($staff)
        ->putJson("/api/v1/form-permintaan/{$form->id}/reject", [
            'reason' => 'Test rejection',
        ])
        ->assertStatus(422);
});

// =====================================================
// DELETE FORM PERMINTAAN TESTS
// =====================================================

test('admin can delete form permintaan', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->assignRole('admin');

    $form = FormPermintaan::create([
        'user_id' => $admin->id,
        'branch_id' => $branch->id,
        'request_number' => 'FP-' . now()->format('Ymd') . '-008',
        'date' => now()->format('Y-m-d'),
        'priority' => 'low',
        'request_type' => 'jasa',
        'status' => 'pending',
    ]);

    actingAs($admin)
        ->deleteJson("/api/v1/form-permintaan/{$form->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(FormPermintaan::find($form->id))->toBeNull();
});

test('user without delete permission cannot delete', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('user');

    $form = FormPermintaan::create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'request_number' => 'FP-' . now()->format('Ymd') . '-009',
        'date' => now()->format('Y-m-d'),
        'priority' => 'medium',
        'request_type' => 'pembelian_produk_baru',
        'reason' => 'Test',
        'status' => 'pending',
    ]);

    actingAs($user)
        ->deleteJson("/api/v1/form-permintaan/{$form->id}")
        ->assertStatus(403);
});

// =====================================================
// FILTER TESTS
// =====================================================

test('can filter form permintaan by request_type', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->assignRole('admin');

    FormPermintaan::create([
        'user_id' => $admin->id,
        'branch_id' => $branch->id,
        'request_number' => 'FP-' . now()->format('Ymd') . '-010',
        'date' => now()->format('Y-m-d'),
        'priority' => 'high',
        'request_type' => 'jasa',
        'status' => 'pending',
    ]);

    actingAs($admin)
        ->getJson('/api/v1/form-permintaan?row_per_page=10&request_type=jasa')
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('can filter form permintaan by status', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/form-permintaan?row_per_page=10&status=pending')
        ->assertOk()
        ->assertJsonPath('success', true);
});
