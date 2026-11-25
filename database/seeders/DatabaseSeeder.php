<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Administrator account
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Administrator', 'password' => 'password', 'role' => 'admin', 'is_active' => true]
        );

        // One demo account per role to try RBAC locally (never created in production).
        if (app()->environment('local', 'testing')) {
            foreach ([
                ['registrar@example.com', 'Rita Registrar', 'registrar'],
                ['cashier@example.com', 'Carlo Cashier', 'cashier'],
                ['instructor@example.com', 'Ian Instructor', 'instructor'],
                ['viewer@example.com', 'Vera Viewer', 'viewer'],
            ] as [$email, $name, $role]) {
                User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => 'password', 'role' => $role, 'is_active' => true]);
            }
        }

        // Membership categories
        $categories = [
            'Student' => 'For currently enrolled students.',
            'Regular' => 'Standard membership for adults.',
            'Senior Citizen' => 'For members aged 60 and above.',
            'Corporate' => 'For companies and organizations.',
        ];

        foreach ($categories as $name => $description) {
            MembershipCategory::updateOrCreate(['name' => $name], ['description' => $description]);
        }

        // Membership levels
        $levels = [
            ['name' => 'Basic', 'fee' => 500, 'validity_months' => 12, 'benefits' => 'Access to member events; monthly newsletter.'],
            ['name' => 'Silver', 'fee' => 1500, 'validity_months' => 12, 'benefits' => 'All Basic benefits; 5% discount on services; priority registration.'],
            ['name' => 'Gold', 'fee' => 3000, 'validity_months' => 12, 'benefits' => 'All Silver benefits; 10% discount; free guest pass (2 per year).'],
            ['name' => 'Platinum', 'fee' => 5000, 'validity_months' => 24, 'benefits' => 'All Gold benefits; 20% discount; VIP lounge access; dedicated support.'],
        ];

        foreach ($levels as $level) {
            MembershipLevel::updateOrCreate(['name' => $level['name']], $level);
        }

        // Sample members (only on a fresh database)
        if (Member::count() === 0) {
            $samples = [
                ['Maria', 'Santos', 'maria.santos@example.com', 'Student', 'Basic'],
                ['Jose', 'Reyes', 'jose.reyes@example.com', 'Regular', 'Silver'],
                ['Lourdes', 'Garcia', 'lourdes.garcia@example.com', 'Senior Citizen', 'Gold'],
                ['Acme', 'Corporation', 'info@acme.example.com', 'Corporate', 'Platinum'],
            ];

            foreach ($samples as [$first, $last, $email, $category, $level]) {
                Member::create([
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => $email,
                    'membership_category_id' => MembershipCategory::where('name', $category)->value('id'),
                    'membership_level_id' => MembershipLevel::where('name', $level)->value('id'),
                    'status' => Member::STATUS_PENDING,
                ]);
            }
        }

        // Sample e-learning courses (only on a fresh database)
        if (Course::count() === 0) {
            $this->seedCourses();
        }

        // Demo member-portal login (local only): Maria Santos can sign in at /login with password "password".
        if (app()->environment('local', 'testing') && $maria = Member::where('email', 'maria.santos@example.com')->first()) {
            User::updateOrCreate(
                ['email' => $maria->email],
                [
                    'name' => $maria->full_name, 'password' => 'password', 'role' => 'member', 'is_active' => true,
                    'member_id' => $maria->id, 'portal_activated_at' => now(),
                ]
            );
        }
    }

    /** Two small courses so the e-learning module can be tried straight away. */
    private function seedCourses(): void
    {
        $welcome = Course::create([
            'title' => 'Welcome to Your Membership',
            'summary' => 'A 10-minute tour of your benefits, the portal and how renewals work.',
            'description' => "Learn what your membership includes, how to use the member portal, and how to keep your membership in good standing.",
            'pass_mark' => 70,
            'is_published' => true,
        ]);

        $welcome->lessons()->createMany([
            [
                'title' => 'What your membership includes',
                'position' => 1,
                'content' => "## Your benefits\n\nEvery membership level comes with access to **member events** and our **monthly newsletter**. Higher levels add discounts, priority registration and more.\n\n- **Basic** – events and newsletter\n- **Silver** – discounts and priority registration\n- **Gold** – larger discounts and guest passes\n- **Platinum** – VIP lounge access and dedicated support\n\nYour level and expiry date are always shown on the **My Membership** page.",
            ],
            [
                'title' => 'Using the member portal',
                'position' => 2,
                'content' => "## Everything in one place\n\nIn the portal you can:\n\n1. Check your status, level and expiry date.\n2. Open **History & Receipts** to view and print official receipts.\n3. Update your phone number and address.\n4. Take courses in **Learning** and download your certificates.\n\n> Name and email changes go through the membership office so your record stays accurate.",
            ],
            [
                'title' => 'Renewing on time',
                'position' => 3,
                'content' => "## How renewal works\n\nYou can request a renewal from the portal starting **90 days before** your membership expires, or any time after it lapses.\n\n1. Press **Renew** and choose your level.\n2. Pay at the membership office and quote your reference number.\n3. Staff approve the payment and your new validity period starts.\n\nRenewing early never loses you any time: the new period continues after your current expiry date.",
            ],
        ]);

        $questions = [
            ['Where can you see your membership expiry date?', ['On the My Membership page', 'Only by calling the office', 'In the newsletter', 'It is not shown anywhere'], 0],
            ['How early can you request a renewal in the portal?', ['30 days before expiry', '90 days before expiry', 'Only after it expires', 'One year before expiry'], 1],
            ['After you request a renewal, what happens next?', ['It is approved automatically', 'You pay at the office, then staff approve it', 'Nothing, you must apply again', 'Your level is downgraded'], 1],
            ['Who can change your name or email address on record?', ['You, from the portal', 'Anyone with the link', 'The membership office', 'Nobody'], 2],
        ];

        foreach ($questions as $position => [$prompt, $options, $correct]) {
            $welcome->questions()->create([
                'prompt' => $prompt,
                'options' => $options,
                'correct_option' => $correct,
                'position' => $position + 1,
            ]);
        }

        $premium = Course::create([
            'title' => 'Making the Most of Premium Benefits',
            'summary' => 'For Silver, Gold and Platinum members: discounts, guest passes and priority registration.',
            'description' => 'A short guide to claiming every benefit your level includes.',
            'membership_level_id' => MembershipLevel::where('name', 'Silver')->value('id'),
            'pass_mark' => 60,
            'is_published' => true,
        ]);

        $premium->lessons()->create([
            'title' => 'Claiming your discounts and priority registration',
            'position' => 1,
            'content' => "## Priority registration\n\nSilver members and above can register for events before the general public. Watch your email for the early-access window.\n\n## Discounts\n\nQuote your **membership number** when paying so the discount for your level is applied.",
        ]);

        $premium->questions()->create([
            'prompt' => 'What should you quote to receive your level discount?',
            'options' => ['Your birthday', 'Your membership number', 'Your phone number', 'Nothing'],
            'correct_option' => 1,
            'position' => 1,
        ]);
    }
}
