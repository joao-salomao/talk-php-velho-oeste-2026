<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Popula:
     *  - 1 customer fixo "ACME Corporation" (pro roteiro do slide 19)
     *    + 9 customers aleatórios.
     *  - 30 tickets curados — subjects/descriptions realistas.
     *    IDs #1–#10 vão pra ACME pro roteiro do palco.
     */
    public function run(): void
    {
        // ACME — usado no roteiro: "List open tickets for ACME"
        $acme = Customer::factory()->create([
            'name' => 'ACME Corporation',
            'email' => 'support@acme.example',
        ]);

        // Mais 9 customers aleatórios. Empresas de verdade pro demo
        // ficar com cara de produção.
        $others = collect([
            ['name' => 'Globex Industries',  'email' => 'help@globex.example'],
            ['name' => 'Initech LLC',        'email' => 'support@initech.example'],
            ['name' => 'Umbrella Co',        'email' => 'tickets@umbrella.example'],
            ['name' => 'Stark Manufacturing','email' => 'it@stark.example'],
            ['name' => 'Wayne Enterprises',  'email' => 'helpdesk@wayne.example'],
            ['name' => 'Tyrell Robotics',    'email' => 'ops@tyrell.example'],
            ['name' => 'Soylent Foods',      'email' => 'admin@soylent.example'],
            ['name' => 'Cyberdyne Systems',  'email' => 'support@cyberdyne.example'],
            ['name' => 'Pied Piper',         'email' => 'team@piedpiper.example'],
        ])->map(fn ($data) => Customer::factory()->create($data));

        $allCustomers = collect([$acme])->merge($others);

        // Tickets curados. Primeiros 10 -> ACME (IDs #1-#10), pra o
        // roteiro do slide 19 funcionar 100% previsível. Os outros 20
        // distribuídos entre os demais customers de forma round-robin.
        foreach ($this->acmeTickets() as $ticket) {
            Ticket::create([...$ticket, 'customer_id' => $acme->id]);
        }

        $otherCustomers = $others->values();
        foreach ($this->otherTickets() as $i => $ticket) {
            $customer = $otherCustomers[$i % $otherCustomers->count()];
            Ticket::create([...$ticket, 'customer_id' => $customer->id]);
        }
    }

    /**
     * Tickets da ACME — IDs #1-#10. Cada um foi pensado pra casar com
     * pelo menos uma entry da KB, então "Propose a solution for ticket
     * #5" sempre acha algo útil.
     *
     * @return array<int, array{subject: string, description: string, status: string, priority: string}>
     */
    private function acmeTickets(): array
    {
        return [
            [
                'subject' => 'Cannot log in after password reset email',
                'description' => "Marina from our marketing team requested a password reset yesterday. The reset link returns 'token expired' even though she clicked it within minutes. She is locked out and cannot access today's campaign reports.",
                'status' => 'open',
                'priority' => 'high',
            ],
            [
                'subject' => 'Authenticator app codes rejected as invalid',
                'description' => "Our entire engineering team rotated phones last week. Since then, Google Authenticator codes are rejected as invalid even though we are entering them within the 30-second window. No one has their backup codes saved.",
                'status' => 'open',
                'priority' => 'urgent',
            ],
            [
                'subject' => 'Webhooks stopped firing this morning around 9am',
                'description' => "We rely on the order.created webhook to push events into our ERP. Last successful delivery in our logs was 09:04 UTC. Nothing since. We have not changed our endpoint or rotated the signing secret recently.",
                'status' => 'open',
                'priority' => 'high',
            ],
            [
                'subject' => 'Double charge on November invoice',
                'description' => "Our finance team flagged that we were billed twice for the Pro plan in November (invoices INV-9821 and INV-9823, both for \$499). Same card, same day. Need a refund or clarification before month-end close.",
                'status' => 'open',
                'priority' => 'normal',
            ],
            [
                'subject' => 'Analytics dashboard takes 18+ seconds to load',
                'description' => "Our exec dashboard has ~60 widgets and used to load in 3-4s. As of this week it spins for 18-20s every refresh. Tried different browsers and incognito. The team is asking if there is a way to speed it up.",
                'status' => 'open',
                'priority' => 'normal',
            ],
            [
                'subject' => 'Okta SSO fails for new hires with "user not provisioned"',
                'description' => "We onboarded 4 new engineers this week. All of them get 'user not provisioned' when they try to SSO via Okta. Existing users sign in fine. We did not change our SAML config.",
                'status' => 'in_progress',
                'priority' => 'high',
            ],
            [
                'subject' => 'Search returns 0 results for a record I just created',
                'description' => "I created project 'Q1 Roadmap' ten minutes ago and confirmed it exists in the UI. Global search returns nothing for 'Q1 Roadmap' or partial matches. Filters are off, role is Admin.",
                'status' => 'open',
                'priority' => 'low',
            ],
            [
                'subject' => 'Hitting 429 rate limit but well under documented quota',
                'description' => "Our integration averages ~300 req/min, peaking around 700 during nightly sync. Today we started seeing HTTP 429 on /v1/records reads even when our metrics show 400 req/min. Has the limit changed?",
                'status' => 'open',
                'priority' => 'high',
            ],
            [
                'subject' => 'Need VAT number added to November invoice',
                'description' => "Our accountant needs the EU VAT ID DE123456789 on invoice INV-9821 for our books. The tax ID was missing when the invoice was issued. Can you re-issue or amend it?",
                'status' => 'waiting_customer',
                'priority' => 'normal',
            ],
            [
                'subject' => 'Workspace storage at 95%, need guidance on cleanup',
                'description' => "We are at 95.2 GB of our 100 GB Pro storage limit. A lot of it is old file attachments from 2022-2023. What is the recommended way to archive old data without losing references?",
                'status' => 'open',
                'priority' => 'normal',
            ],
        ];
    }

    /**
     * Outros 20 tickets — mix de customers, statuses e prioridades.
     * Round-robin entre os 9 customers não-ACME (~2 tickets por
     * customer). Mesma lógica de cobertura ampla da KB.
     *
     * @return array<int, array{subject: string, description: string, status: string, priority: string}>
     */
    private function otherTickets(): array
    {
        return [
            [
                'subject' => 'OAuth refresh token returns invalid_grant',
                'description' => 'Our background sync started failing overnight. Access token expired (expected after 1h) but the refresh call returns invalid_grant. The refresh_token has only been in use for ~3 weeks.',
                'status' => 'open',
                'priority' => 'high',
            ],
            [
                'subject' => 'Cannot edit a project I created last sprint',
                'description' => 'I created project Phoenix two weeks ago. Today the Edit button is greyed out. My role still shows as Editor in member settings. The project itself looks fine — just read-only for me now.',
                'status' => 'open',
                'priority' => 'normal',
            ],
            [
                'subject' => 'Daily scheduled report did not send this morning',
                'description' => 'Our 8am UTC weekday revenue report did not arrive today. Last delivery was Friday. The dataset is not empty — I verified by running the underlying query. Recipient list has 14 people, none reported bounces.',
                'status' => 'open',
                'priority' => 'normal',
            ],
            [
                'subject' => 'Mobile app logs me out every time I switch apps',
                'description' => 'iOS app v3.4.1, iPhone 15. As soon as I switch to another app and come back, I am back at the login screen. Started after the last update. Re-auth on switch is OFF in my settings.',
                'status' => 'open',
                'priority' => 'low',
            ],
            [
                'subject' => 'Need to permanently delete customer PII record',
                'description' => 'Per a GDPR erasure request, we need to fully purge customer record CUS-44827 — not soft-delete. Cannot find a "permanent delete" option in the UI. Is this only available via API?',
                'status' => 'open',
                'priority' => 'high',
            ],
            [
                'subject' => 'Want to switch from monthly to annual billing',
                'description' => 'We are on the Business monthly plan (\$1200/mo). Looking at the annual discount. What happens to the unused portion of this month if we switch mid-cycle?',
                'status' => 'open',
                'priority' => 'low',
            ],
            [
                'subject' => 'Getting too many notification emails',
                'description' => 'I am getting 80+ emails a day from comments and mentions. I do not want to turn notifications off entirely — just batch them. Is there a digest option?',
                'status' => 'resolved',
                'priority' => 'low',
            ],
            [
                'subject' => 'Webhook signature verification keeps failing',
                'description' => 'I am computing HMAC-SHA256 of the raw body using the secret from settings and comparing to X-Signature. It never matches. I have triple-checked the secret. Is the body the raw JSON or the parsed object?',
                'status' => 'in_progress',
                'priority' => 'normal',
            ],
            [
                'subject' => 'How do I export everything for our compliance audit?',
                'description' => 'External auditor needs CSVs of all records, users, and audit log entries for the last 12 months. Workspace has ~2M records. Is there an async export endpoint or do I have to paginate?',
                'status' => 'open',
                'priority' => 'normal',
            ],
            [
                'subject' => 'API latency spike between 14:00-15:00 UTC daily',
                'description' => 'p95 latency on our integration endpoint jumps from ~180ms to 2.5s every weekday between 14:00 and 15:00 UTC. Our request volume is flat during that window. Could it be a noisy neighbor?',
                'status' => 'open',
                'priority' => 'high',
            ],
            [
                'subject' => 'Need to transfer workspace ownership to a different admin',
                'description' => 'Our founder Sarah is leaving and we need to transfer workspace ownership to our new CTO. Both are Admins already. What is the exact process?',
                'status' => 'open',
                'priority' => 'normal',
            ],
            [
                'subject' => 'Cannot add a new teammate — invite link does nothing',
                'description' => 'I am sending invites to new-engineer@piedpiper.example. The form says "Invitation sent" but the recipient does not receive an email. Checked spam, asked them to whitelist our domain. No dice.',
                'status' => 'waiting_customer',
                'priority' => 'normal',
            ],
            [
                'subject' => 'Are you having an outage right now?',
                'description' => 'Our entire team cannot access the dashboard since 13:42 UTC. We get a 503 on the main domain. status.example.com still shows all systems operational. Is there an active incident?',
                'status' => 'resolved',
                'priority' => 'urgent',
            ],
            [
                'subject' => 'Downgrade from Business to Pro — what changes?',
                'description' => 'We are downsizing and want to drop from Business (\$1200/mo) to Pro (\$249/mo). We currently use 380 GB of storage which is above the Pro 100 GB limit. What happens to the excess data?',
                'status' => 'open',
                'priority' => 'low',
            ],
            [
                'subject' => '2FA recovery codes do not work',
                'description' => 'I lost my phone (and Authenticator app). I have my recovery codes from initial setup. None of them are accepted at the 2FA prompt. They look fine — 8-char alphanumeric, no whitespace.',
                'status' => 'in_progress',
                'priority' => 'urgent',
            ],
            [
                'subject' => 'How do I batch-update records via API?',
                'description' => 'I need to update the status field on ~5000 records. Doing this one-by-one is hitting rate limits and taking hours. Is there a bulk endpoint? Did not see one in the docs.',
                'status' => 'open',
                'priority' => 'normal',
            ],
            [
                'subject' => 'Webhook delivery shows 200 but our endpoint did not receive',
                'description' => 'Settings → Webhooks → Recent deliveries shows all my deliveries as 200 OK. But our endpoint logs are empty. We do not see any incoming requests at all. Could there be a routing issue on your side?',
                'status' => 'open',
                'priority' => 'high',
            ],
            [
                'subject' => 'Cannot download invoice PDF older than 12 months',
                'description' => 'Need to download invoices from Q1 2024 for our annual audit. The PDF icons are missing for everything before this month. Are old invoices deleted?',
                'status' => 'resolved',
                'priority' => 'low',
            ],
            [
                'subject' => 'Reset password email never arrives',
                'description' => 'A teammate Tom Riddle tried "Forgot password" three times yesterday and twice today. Reset email never arrives. Not in spam, not in junk, not in quarantine. Other emails from your domain (notifications) arrive fine.',
                'status' => 'open',
                'priority' => 'high',
            ],
            [
                'subject' => 'Notifications: only @mentions, no comment-noise',
                'description' => 'I want email notifications ONLY when I am @mentioned, not for every comment in a thread I am subscribed to. The in-app inbox can keep everything. Possible?',
                'status' => 'open',
                'priority' => 'low',
            ],
        ];
    }

}
