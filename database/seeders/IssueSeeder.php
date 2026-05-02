<?php

namespace Database\Seeders;

use App\Issue\Models\Category;
use App\Issue\Models\Issue;
use App\Issue\Models\PlaybookEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class IssueSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now();

        foreach ($this->seedData($now) as $data) {
            $category = Category::findBySlug($data['category_slug']);
            $playbook = isset($data['playbook_slug'])
                ? PlaybookEntry::findBySlug($data['playbook_slug'])
                : null;

            $issue = Issue::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $category->id,
                'status' => $data['status'],
                'due_date' => $data['due_date'] ?? null,
                'issuer' => $data['issuer'] ?? null,
                'issuer_email' => $data['issuer_email'] ?? null,
                'summary' => $playbook?->summary_template,
                'suggested_action' => $playbook?->suggested_action,
            ]);

            if (isset($data['created_at'])) {
                $issue->created_at = $data['created_at'];
                $issue->updated_at = $data['created_at'];
                $issue->saveQuietly();
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function seedData(CarbonImmutable $now): array
    {
        return [
            [
                'title' => 'Cannot log in after password reset',
                'description' => "After resetting my password from the email link, I still can't log in. Keeps saying 'invalid credentials' even though I'm using the new password.",
                'category_slug' => 'support',
                'status' => 'in_progress',
                'issuer' => 'Maria Santos',
                'issuer_email' => 'maria.santos@example.com',
                'playbook_slug' => 'login_registration',
                'created_at' => $now->subHours(3),
            ],
            [
                'title' => 'Production checkout returning 500 errors',
                'description' => 'All checkout attempts on prod are throwing 500 errors. Started 10 minutes ago. Customers cannot complete purchases.',
                'category_slug' => 'incident',
                'status' => 'open',
                'due_date' => $now->subHour(),
                'issuer' => 'Ops Bot',
                'issuer_email' => 'ops@example.com',
                'playbook_slug' => 'app_site_issues',
                'created_at' => $now->subMinutes(15),
            ],
            [
                'title' => 'Promo code SUMMER25 not applying',
                'description' => 'Trying to use SUMMER25 at checkout but it says invalid. The email I got says it expires next week.',
                'category_slug' => 'support',
                'status' => 'open',
                'issuer' => 'Tom Reilly',
                'issuer_email' => 'tom@example.com',
                'playbook_slug' => 'promo_codes',
                'created_at' => $now->subHours(6),
            ],
            [
                'title' => 'Order shipped but tracking shows no movement',
                'description' => "Order #42198 was marked shipped 4 days ago but the tracking page hasn't updated since. I'm worried the package is lost.",
                'category_slug' => 'support',
                'status' => 'open',
                'due_date' => $now->addDays(2),
                'issuer' => 'Emily Chen',
                'playbook_slug' => 'delivery_shipping',
                'created_at' => $now->subDays(1),
            ],
            [
                'title' => 'Charged twice for the same order',
                'description' => 'My credit card statement shows two charges of $89.99 for order #41872. Only placed it once. Need a refund for the duplicate.',
                'category_slug' => 'incident',
                'status' => 'in_progress',
                'due_date' => $now->subDays(2),
                'issuer' => 'Alex Park',
                'issuer_email' => 'alex.park@example.com',
                'playbook_slug' => 'payment_billing',
                'created_at' => $now->subDays(3),
            ],
            [
                'title' => 'Add bulk export button to admin dashboard',
                'description' => "It would be great if we could export the entire orders table as CSV instead of having to scroll through pagination. Would save the ops team hours per week.",
                'category_slug' => 'feature_request',
                'status' => 'open',
                'issuer' => 'Jordan Lee',
                'issuer_email' => 'jordan@example.com',
                'playbook_slug' => 'feature_requests',
                'created_at' => $now->subDays(7),
            ],
            [
                'title' => 'Mobile app crashes on iOS 17 launch',
                'description' => 'After updating to iOS 17, the app crashes immediately on launch. Reinstalled twice. iPhone 14 Pro.',
                'category_slug' => 'bug',
                'status' => 'resolved',
                'issuer' => 'Sam Iversen',
                'issuer_email' => 'sam.iversen@example.com',
                'playbook_slug' => 'app_site_issues',
                'created_at' => $now->subDays(10),
            ],
            [
                'title' => 'How do I delete my account permanently?',
                'description' => "I want to permanently delete my account and have all my data removed. Can you guide me through the steps or do it for me?",
                'category_slug' => 'support',
                'status' => 'closed',
                'issuer' => 'Priya Iyer',
                'issuer_email' => 'priya@example.com',
                'playbook_slug' => 'account_settings',
                'created_at' => $now->subDays(14),
            ],
            [
                'title' => 'Password reset email never arrives',
                'description' => 'Requested a password reset 5 times, none of the emails came through. Checked spam folder. Email is correct.',
                'category_slug' => 'support',
                'status' => 'in_progress',
                'due_date' => $now->subHours(12),
                'issuer' => 'Lin Zhao',
                'issuer_email' => 'lin.zhao@example.com',
                'playbook_slug' => 'password_security',
                'created_at' => $now->subHours(18),
            ],
            [
                'title' => 'Random suggestion about recommendations',
                'description' => 'Just a thought — would be cool if the homepage recommendations changed based on time of day. Mornings showing breakfast items, etc.',
                'category_slug' => 'other',
                'status' => 'open',
                'created_at' => $now->subDays(5),
            ],
        ];
    }
}
