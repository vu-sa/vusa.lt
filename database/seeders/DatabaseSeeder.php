<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Document;
use App\Models\Doing;
use App\Models\Duty;
use App\Models\Goal;
use App\Models\Institution;
use App\Models\Matter;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\QuickLink;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CategoriesSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(TenantSeeder::class);
        $this->call(TypeSeeder::class);
        $this->call(MemberRegistrationFormSeeder::class);

        // Generate Typesense search key if needed
        $this->generateTypesenseSearchKey();

        $tenants = Tenant::all();
        $categories = Category::all();

        // Create main page content for the pagrindinis tenant
        $this->createMainPageContent();

        // Create study programs for each tenant
        $studyPrograms = StudyProgram::factory(30)->recycle($tenants)->create();

        Institution::factory(50)
            ->has(Matter::factory(3))
            ->has(Meeting::factory(3)->has(AgendaItem::factory(3)))
            ->recycle($tenants)
            ->withType()
            ->create();

        Institution::factory()
            ->state(['name' => 'Centrinis biuras', 'alias' => 'centrinis-biuras'])
            ->withType()
            ->recycle(Tenant::where('alias', 'vusa')->get())
            ->has(Duty::factory(10)->withType()->hasAttached(
                User::factory()->recycle($studyPrograms),
                ['start_date' => now()->subDay()]
            ))
            ->create();

        $users = User::factory(25)
            ->recycle($studyPrograms)
            ->has(Doing::factory(5)->withType())
            ->hasAttached(Duty::factory(3)->withType(), ['start_date' => now()->subDay()])
            ->create();

        // After all dutiables are created, randomly assign study programs to 60% of them
        $this->assignStudyProgramsToDutiables($studyPrograms);

        $this->call(MenuSeeder::class);
        $this->call(DeleteAndSeedPermissions::class);

        Banner::factory(20)->recycle($tenants)->create();
        Calendar::factory(50)->recycle($tenants)->recycle($categories)->create();
        News::factory(75)->recycle($tenants)->create();
        Page::factory(75)->recycle($tenants)->create();

        // Create documents with varied types and languages
        Document::factory(30)
            ->recycle(Institution::all()) // Use existing institutions
            ->create();

        // Create some specific document types
        Document::factory(10)
            ->recycle(Institution::all())
            ->policy()
            ->create();

        Document::factory(15)
            ->recycle(Institution::all())
            ->meetingMinutes()
            ->create();

        Document::factory(8)
            ->recycle(Institution::all())
            ->annualReport()
            ->create();

        // Create some Lithuanian-specific documents
        Document::factory(12)
            ->recycle(Institution::all())
            ->lithuanian()
            ->create();

        // Create some English-specific documents
        Document::factory(8)
            ->recycle(Institution::all())
            ->english()
            ->create();

        Resource::factory(50)->has(Reservation::factory()->hasAttached($users->random(3)))->recycle($tenants)->create();

        $this->call(RoleStudentRepresentativeSeeder::class);
        $this->call(RoleStudentRepresentativeCoordinatorSeeder::class);
        $this->call(RoleCommunicationCoordinatorSeeder::class);
        $this->call(RoleGlobalCommunicationCoordinatorSeeder::class);
        $this->call(RoleResourceManagerSeeder::class);

        Goal::factory(10)->recycle($tenants)->create();

        foreach ($tenants as $tenant) {
            QuickLink::factory(6)
                ->recycle($tenant)
                ->state(new Sequence(['lang' => 'lt'], ['lang' => 'en']))
                ->state(new Sequence(
                    ['link' => '/lt/kontaktai/koordinatoriai', 'text' => 'Koordinatoriai'],
                    ['link' => '/en/kontaktai/koordinatoriai', 'text' => 'Coordinators'],
                    ['link' => '/lt/kontaktai/kuratoriai', 'text' => 'Kuratoriai'],
                    ['link' => '/en/kontaktai/kuratoriai', 'text' => 'Curators'],
                    ['link' => '/lt/kontaktai/studentu-atstovai', 'text' => 'Studentų atstovai'],
                    ['link' => '/en/kontaktai/studentu-atstovai', 'text' => 'Student representatives'],
                ))->create();
        }

        QuickLink::factory(200)->recycle($tenants)->create();
    }

    /**
     * Assign study programs to approximately 60% of dutiables
     */
    private function assignStudyProgramsToDutiables($studyPrograms)
    {
        Dutiable::chunk(50, function ($dutiables) use ($studyPrograms) {
            foreach ($dutiables as $dutiable) {
                // 60% chance to assign a study program
                if (fake()->boolean(60)) {
                    $dutiable->study_program_id = $studyPrograms->random()->id;
                    $dutiable->save();
                }
            }
        });
    }

    /**
     * Create main page content for the pagrindinis tenant
     */
    private function createMainPageContent()
    {
        $pagrindinisTenant = Tenant::where('type', 'pagrindinis')->first();

        if (! $pagrindinisTenant) {
            return;
        }

        // Create main content
        $content = Content::create([]);

        // Create content parts similar to the current structure
        ContentPart::create([
            'content_id' => $content->id,
            'type' => 'news',
            'json_content' => [],
            'options' => null,
            'order' => 0,
        ]);

        ContentPart::create([
            'content_id' => $content->id,
            'type' => 'calendar',
            'json_content' => [],
            'options' => null,
            'order' => 1,
        ]);

        // Update the tenant to reference this content
        $pagrindinisTenant->update(['content_id' => $content->id]);
    }

    /**
     * Generate Typesense search key if not already configured
     */
    private function generateTypesenseSearchKey()
    {
        // Skip search key generation during testing
        if (app()->environment('testing')) {
            return;
        }

        $adminKey = config('scout.typesense.client-settings.api_key');
        $searchKey = config('scout.typesense.client-settings.search_only_key');

        // Skip if admin key is not configured properly or search key already exists
        if (empty($adminKey) || in_array($adminKey, ['xyz', 'xyza'], true) || !empty($searchKey)) {
            if (in_array($adminKey, ['xyz', 'xyza'], true)) {
                $this->command->warn('⚠️  TYPESENSE_API_KEY is using placeholder value. Set a real API key to enable search features.');
            }
            return;
        }

        try {
            // Try to generate search key using the command
            $exitCode = Artisan::call('typesense:generate-search-key');
            
            if ($exitCode === 0) {
                $this->command->info('✅ Generated Typesense search-only key');
            } else {
                $this->command->warn('⚠️  Could not generate Typesense search key - service may not be available');
            }
        } catch (\Exception $e) {
            $this->command->warn('⚠️  Could not generate Typesense search key: ' . $e->getMessage());
        }
    }
}
