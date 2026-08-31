<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['email' => 'admin@abbadev.com']);
    }

    protected function payment(): Payment
    {
        $event = Event::create([
            'slug' => 'idea-to-intelligent-system',
            'title' => 'From Idea to Intelligent System',
            'event_code' => 'SEM',
            'expected_amount' => 399.00,
            'is_active' => true,
        ]);

        $registration = Registration::create([
            'registration_number' => Registration::generateNumber($event),
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'event_id' => $event->id,
        ]);

        return $registration->payments()->create([
            'payment_method' => 'gcash',
            'expected_amount' => 399.00,
            'amount_submitted' => 399.00,
            'reference_number' => '1000123456789',
            'status' => PaymentStatus::ForVerification,
        ]);
    }

    public function test_login_page_loads(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_list_page_renders_with_payment(): void
    {
        $payment = $this->payment();

        Livewire::actingAs($this->admin())
            ->test(ListPayments::class)
            ->assertOk()
            ->assertSee($payment->registration->registration_number);
    }

    public function test_view_page_renders(): void
    {
        $payment = $this->payment();

        Livewire::actingAs($this->admin())
            ->test(ViewPayment::class, ['record' => $payment->getRouteKey()])
            ->assertOk()
            ->assertSee('1000123456789');
    }
}
