<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Posting;
use App\Models\Project;
use App\Models\SalaryStructure;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dummy data for trying the system out: divisions, departments, Head Office,
 * projects, designations and ~60 employees with posting and salary histories
 * and personal details. All of it is made up.
 *
 * Every dummy record has a code starting with "DEMO-", so it can be removed
 * cleanly without touching real records:
 *
 *   php artisan demo:data            add the dummy data
 *   php artisan demo:data --remove   remove it again
 *   php artisan demo:data --fresh    remove and add again
 *
 * `php artisan db:seed` (and `migrate:fresh --seed`) also adds it.
 */
class DemoData extends Command
{
    protected $signature = 'demo:data {--remove : Delete the dummy records} {--fresh : Delete and re-create the dummy records}';

    protected $description = 'Add (or remove) dummy divisions, projects and employees for trying the system out';

    /** Divisions use DDC's sector names from ddclbd.com; departments are made up. */
    private const STRUCTURE = [
        'Building & Planning' => ['Architecture', 'Structural Design', 'Planning'],
        'Roads & Bridges' => ['Highway Design', 'Bridge Engineering', 'Traffic & Transport'],
        'ICT' => ['Software', 'GIS & Remote Sensing'],
        'Port & Railway' => ['Port Engineering', 'Railway Design'],
        'Energy & Power' => ['Power Systems'],
        'Rural Development' => ['Rural Infrastructure'],
        'Water Resource & Environment' => ['Hydrology', 'Environment & Social'],
    ];

    private const DESIGNATIONS = [
        ['Managing Director', 'G-1'], ['Director', 'G-2'], ['Team Leader', 'G-3'], ['Senior Engineer', 'G-4'],
        ['Engineer', 'G-5'], ['Assistant Engineer', 'G-6'], ['Senior Architect', 'G-4'], ['Architect', 'G-5'],
        ['Planner', 'G-5'], ['GIS Specialist', 'G-5'], ['Software Engineer', 'G-5'], ['Environmental Specialist', 'G-5'],
        ['Quantity Surveyor', 'G-6'], ['CAD Operator', 'G-7'], ['Admin Officer', 'G-6'], ['Accounts Officer', 'G-6'],
    ];

    private const PROJECTS = [
        // name, division, status, client, location, months since start (negative = starts later)
        ['Dhaka–Mymensingh Highway Widening', 'Roads & Bridges', 'ongoing', 'Roads and Highways Department', 'Gazipur', 26],
        ['Upazila Bridges Package 3', 'Roads & Bridges', 'ongoing', 'LGED', 'Sylhet', 14],
        ['District Hospital Complex', 'Building & Planning', 'ongoing', 'Health Engineering Department', 'Rajshahi', 20],
        ['Government Office Tower', 'Building & Planning', 'ongoing', 'Public Works Department', 'Dhaka', 9],
        ['City Drainage Master Plan', 'Water Resource & Environment', 'ongoing', 'City Corporation', 'Chattogram', 11],
        ['River Embankment Study', 'Water Resource & Environment', 'planned', 'Water Development Board', 'Sirajganj', -2],
        ['Inland Container Terminal', 'Port & Railway', 'ongoing', 'Port Authority', 'Narayanganj', 17],
        ['Rail Line Feasibility Study', 'Port & Railway', 'completed', 'Bangladesh Railway', 'Khulna', 40],
        ['Substation Upgrade Programme', 'Energy & Power', 'ongoing', 'Power Development Board', 'Bogura', 8],
        ['Land Records GIS Portal', 'ICT', 'ongoing', 'Ministry of Land', 'Dhaka', 6],
        ['Rural Market Infrastructure', 'Rural Development', 'completed', 'LGED', 'Rangpur', 30],
    ];

    private const FIRST = ['Rahim', 'Nasrin', 'Kamal', 'Farhana', 'Tanvir', 'Sharmin', 'Mahmudul', 'Ayesha', 'Rafiqul', 'Nusrat', 'Shahidul', 'Tahmina', 'Arif', 'Sabrina', 'Imran', 'Jannatul', 'Mizanur', 'Rumana', 'Saiful', 'Nazia', 'Habibur', 'Laila', 'Zahid', 'Moushumi', 'Anisur', 'Shirin', 'Fahim', 'Rokeya', 'Sohel', 'Tasnim', 'Mostafa', 'Fatema', 'Golam', 'Shamima', 'Rezaul', 'Afroza', 'Masud', 'Sultana', 'Nazmul', 'Rehana'];

    private const LAST = ['Uddin', 'Akter', 'Hossain', 'Islam', 'Ahmed', 'Sultana', 'Hasan', 'Siddika', 'Rahman', 'Jahan', 'Alam', 'Begum', 'Chowdhury', 'Kabir', 'Ferdous', 'Haque', 'Mahmud', 'Khatun', 'Rana', 'Ara', 'Karim', 'Sarker', 'Bhuiyan', 'Talukder', 'Miah', 'Das', 'Roy', 'Biswas'];

    private const AREAS = ['Dhanmondi, Dhaka', 'Mirpur, Dhaka', 'Uttara, Dhaka', 'Mohammadpur, Dhaka', 'Bashundhara, Dhaka', 'Gulshan, Dhaka', 'Agrabad, Chattogram', 'Shahjalal Upashahar, Sylhet', 'Kazla, Rajshahi', 'Sonadanga, Khulna', 'Tongi, Gazipur', 'Kotwali, Cumilla'];

    private const HOME_DISTRICTS = ['Barishal', 'Bogura', 'Cumilla', 'Faridpur', 'Jessore', 'Kushtia', 'Mymensingh', 'Noakhali', 'Pabna', 'Rangpur', 'Sylhet', 'Tangail'];

    private const BANKS = ['Sonali Bank', 'Dutch-Bangla Bank', 'BRAC Bank', 'Eastern Bank', 'City Bank', 'Islami Bank', 'Prime Bank'];

    private const COUNT = 60;

    public function handle(): int
    {
        if ($this->option('remove') || $this->option('fresh')) {
            $this->remove();
            $this->info('Dummy data removed.');
            if ($this->option('remove')) {
                return self::SUCCESS;
            }
        }

        if (Employee::withTrashed()->where('employee_code', 'like', 'DEMO-%')->exists()) {
            $this->warn('Dummy data is already there. Use --fresh to re-create it or --remove to delete it.');

            return self::SUCCESS;
        }

        mt_srand(2026); // same dummy data every time
        DB::transaction(fn () => $this->install());

        $this->info('Added dummy data: '.count(self::STRUCTURE).' divisions, '.Department::whereHas('division', fn ($d) => $d->where('code', 'like', 'DEMO-%'))->count().' departments, Head Office, '.count(self::PROJECTS).' projects, '.count(self::DESIGNATIONS).' designations and '.self::COUNT.' employees.');
        $this->line('Remove it any time with: php artisan demo:data --remove');

        return self::SUCCESS;
    }

    private function install(): void
    {
        // ---- Organisation ----
        $divisions = [];
        $departments = collect();
        $i = 0;
        foreach (self::STRUCTURE as $divName => $deps) {
            $i++;
            $divisions[$divName] = Division::create([
                'name' => $divName,
                'code' => 'DEMO-D'.$i,
                'description' => "Dummy record for the {$divName} division.",
            ]);
            foreach ($deps as $depName) {
                $departments->push(Department::create([
                    'division_id' => $divisions[$divName]->id,
                    'name' => $depName,
                    'code' => 'DEMO-'.strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $depName), 0, 4)).$departments->count(),
                ]));
            }
        }

        $designations = collect(self::DESIGNATIONS)->map(fn ($d) => Designation::firstOrCreate(['title' => $d[0]], ['grade' => $d[1]]))->keyBy('title');

        $hq = Office::create([
            'name' => 'Head Office',
            'code' => 'DEMO-HQ',
            'city' => 'Dhaka',
            'address' => 'Dummy address, Dhaka',
            'is_headquarters' => true,
        ]);

        $projects = collect(self::PROJECTS)->map(function ($p, $n) use ($divisions) {
            [$name, $div, $status, $client, $location, $monthsAgo] = $p;
            $start = now()->subMonths($monthsAgo)->startOfMonth();

            return Project::create([
                'name' => $name,
                'code' => 'DEMO-P'.str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT),
                'division_id' => $divisions[$div]->id,
                'client' => $client,
                'location' => $location,
                'status' => $status,
                'start_date' => $start,
                'end_date' => $status === 'completed' ? now()->subMonths(4 + $n % 5)->endOfMonth() : $start->copy()->addMonths(36),
                'description' => "Dummy project for trying out the system ({$client}).",
            ]);
        });
        $openProjects = $projects->where('status', 'ongoing')->values();
        $startedProjects = $projects->where('status', '!=', 'planned')->values(); // past postings can't be on a project that hasn't started

        // ---- People ----
        $titlesByDivision = [
            'Building & Planning' => ['Senior Architect', 'Architect', 'Senior Engineer', 'Engineer', 'Planner', 'Quantity Surveyor', 'CAD Operator'],
            'Roads & Bridges' => ['Team Leader', 'Senior Engineer', 'Engineer', 'Assistant Engineer', 'Quantity Surveyor', 'CAD Operator'],
            'ICT' => ['Software Engineer', 'GIS Specialist', 'Senior Engineer'],
            'Port & Railway' => ['Team Leader', 'Senior Engineer', 'Engineer', 'Assistant Engineer'],
            'Energy & Power' => ['Senior Engineer', 'Engineer', 'Assistant Engineer'],
            'Rural Development' => ['Engineer', 'Assistant Engineer', 'Planner'],
            'Water Resource & Environment' => ['Environmental Specialist', 'Senior Engineer', 'Engineer', 'GIS Specialist'],
        ];

        $usedNames = [];
        for ($n = 0; $n < self::COUNT; $n++) {
            do {
                $name = self::FIRST[mt_rand(0, count(self::FIRST) - 1)].' '.self::LAST[mt_rand(0, count(self::LAST) - 1)];
            } while (isset($usedNames[$name]));
            $usedNames[$name] = true;

            $dept = $departments[$n % $departments->count()];
            $divName = $dept->division->name;
            $title = match (true) {
                $n === 0 => 'Managing Director',
                $n < 3 => 'Director',
                $n % 13 === 0 => 'Admin Officer',
                $n % 17 === 0 => 'Accounts Officer',
                default => $titlesByDivision[$divName][mt_rand(0, count($titlesByDivision[$divName]) - 1)],
            };

            $joined = Carbon::create(mt_rand(2005, 2025), mt_rand(1, 12), mt_rand(1, 28));
            $born = Carbon::create(mt_rand(1966, 2000), mt_rand(1, 12), mt_rand(1, 28));
            if ($born->copy()->addYears(22)->gt($joined)) {
                $born = $joined->copy()->subYears(mt_rand(23, 35));
            }
            $female = in_array(explode(' ', $name)[0], ['Nasrin', 'Farhana', 'Sharmin', 'Ayesha', 'Nusrat', 'Tahmina', 'Sabrina', 'Jannatul', 'Rumana', 'Nazia', 'Laila', 'Moushumi', 'Shirin', 'Rokeya', 'Tasnim', 'Fatema', 'Shamima', 'Afroza', 'Sultana', 'Rehana'], true);
            $status = match (true) {
                $n % 19 === 7 => 'on_leave',
                $n % 23 === 11 => 'resigned',
                $n === 41 => 'retired',
                default => 'active',
            };
            $slug = strtolower(str_replace(' ', '.', $name));

            $employee = Employee::create([
                'employee_code' => sprintf('DEMO-%03d', $n + 1),
                'name' => $name,
                'designation_id' => $designations[$title]->id,
                'department_id' => $dept->id,
                'status' => $status,
                'employment_type' => $n % 7 === 3 ? 'contractual' : ($n % 11 === 5 ? 'project' : 'permanent'),
                'joining_date' => $joined,
                'office_email' => "{$slug}@example.com",
                'office_phone' => '+880 2 '.mt_rand(5500000, 5599999),
                'personal_email' => "{$slug}.home@example.com",
                'personal_phone' => '01'.mt_rand(3, 9).mt_rand(10000000, 99999999),
                'date_of_birth' => $born,
                'gender' => $female ? 'female' : 'male',
                'blood_group' => Employee::BLOOD_GROUPS[mt_rand(0, 7)],
                'nid_no' => (string) mt_rand(1000000000, 9999999999),
                'tin_no' => (string) mt_rand(100000000000, 999999999999),
                'passport_no' => $n % 3 === 0 ? 'A'.mt_rand(10000000, 99999999) : null,
                'present_address' => 'House '.mt_rand(1, 120).', Road '.mt_rand(1, 30).', '.self::AREAS[mt_rand(0, count(self::AREAS) - 1)],
                'permanent_address' => 'Village '.self::LAST[mt_rand(0, count(self::LAST) - 1)].' Para, '.self::HOME_DISTRICTS[mt_rand(0, count(self::HOME_DISTRICTS) - 1)],
                'emergency_contact_name' => self::FIRST[mt_rand(0, count(self::FIRST) - 1)].' '.explode(' ', $name)[1],
                'emergency_contact_relation' => ['Spouse', 'Father', 'Mother', 'Brother', 'Sister'][mt_rand(0, 4)],
                'emergency_contact_phone' => '01'.mt_rand(3, 9).mt_rand(10000000, 99999999),
                'bank_name' => self::BANKS[mt_rand(0, count(self::BANKS) - 1)],
                'bank_account_no' => (string) mt_rand(1000000000, 9999999999).mt_rand(100, 999),
            ]);

            $this->postings($employee, $joined, $hq, $startedProjects, $openProjects, $n, $title);
            $this->salaries($employee, $joined, $title, $n);

            if ($status === 'resigned' || $status === 'retired') {
                $employee->postings()->whereNull('end_date')->update(['end_date' => now()->subMonths(mt_rand(1, 10))]);
            }
        }
    }

    /** 1–3 postings: some start at HQ and move to a project, some come back, the rest stay at HQ. */
    private function postings(Employee $e, Carbon $joined, Office $hq, $projects, $openProjects, int $n, string $title): void
    {
        $directors = in_array($title, ['Managing Director', 'Director', 'Admin Officer', 'Accounts Officer'], true);
        $pattern = $directors ? 'hq' : ['hq', 'project', 'hq-project', 'project-hq', 'hq-project-project'][$n % 5];
        $steps = explode('-', $pattern);

        $span = max(60, $joined->diffInDays(now()));
        $cursor = $joined->copy();
        foreach ($steps as $k => $type) {
            $isLast = $k === count($steps) - 1;
            $end = $isLast ? null : $cursor->copy()->addDays((int) ($span / count($steps)) - 1);

            // Earlier project postings can be on finished projects; the current one is always ongoing.
            $project = $type === 'project'
                ? ($isLast ? $openProjects[$n % $openProjects->count()] : $projects[($n + $k) % $projects->count()])
                : null;

            $e->postings()->create([
                'posting_type' => $type === 'hq' ? Posting::HQ : Posting::PROJECT,
                'office_id' => $type === 'hq' ? $hq->id : null,
                'project_id' => $project?->id,
                'role_on_posting' => $type === 'project' && in_array($title, ['Team Leader', 'Senior Engineer'], true) ? ($title === 'Team Leader' ? 'Team Leader' : 'Deputy Team Leader') : ($type === 'project' ? 'Site Engineer' : null),
                'start_date' => $cursor,
                'end_date' => $end,
                'order_ref' => 'DDC/HR/'.$cursor->format('Y').'/'.str_pad((string) mt_rand(1, 400), 3, '0', STR_PAD_LEFT),
            ]);
            $cursor = $end?->copy()->addDay() ?? $cursor;
        }
    }

    /** Starting salary by grade, with an increment roughly every 2–3 years. */
    private function salaries(Employee $e, Carbon $joined, string $title, int $n): void
    {
        $base = [
            'Managing Director' => 250000, 'Director' => 180000, 'Team Leader' => 140000, 'Senior Engineer' => 95000,
            'Senior Architect' => 95000, 'Engineer' => 65000, 'Architect' => 65000, 'Planner' => 60000,
            'GIS Specialist' => 60000, 'Software Engineer' => 70000, 'Environmental Specialist' => 65000,
            'Assistant Engineer' => 45000, 'Quantity Surveyor' => 45000, 'CAD Operator' => 32000,
            'Admin Officer' => 40000, 'Accounts Officer' => 42000,
        ][$title];

        $from = $joined->copy();
        $basic = round($base * 0.6, -2);
        while (true) {
            $next = $from->copy()->addMonths(mt_rand(24, 36));
            $isCurrent = $next->gt(now());
            $parts = [
                'basic' => $basic,
                'house_rent' => round($basic * 0.5, -2),
                'medical' => 3000,
                'conveyance' => 2500,
                'other_allowance' => $n % 4 === 0 ? 5000 : 0,
            ];
            $e->salaries()->create([
                ...$parts,
                'gross' => SalaryStructure::grossOf($parts),
                'effective_from' => $from,
                'effective_to' => $isCurrent ? null : $next->copy()->subDay(),
            ]);
            if ($isCurrent) {
                break;
            }
            $from = $next;
            $basic = round($basic * (1 + mt_rand(8, 15) / 100), -2);
        }
    }

    private function remove(): void
    {
        DB::transaction(function () {
            $employees = Employee::withTrashed()->where('employee_code', 'like', 'DEMO-%')->get();
            foreach ($employees as $e) {
                $e->postings()->delete();
                $e->salaries()->delete();
                $e->forceDelete();
            }
            Project::withTrashed()->where('code', 'like', 'DEMO-%')->forceDelete();
            Office::withTrashed()->where('code', 'like', 'DEMO-%')->forceDelete();
            $divisionIds = Division::withTrashed()->where('code', 'like', 'DEMO-%')->pluck('id');
            Department::withTrashed()->whereIn('division_id', $divisionIds)->forceDelete();
            Division::withTrashed()->whereIn('id', $divisionIds)->forceDelete();
            $titles = array_column(self::DESIGNATIONS, 0);
            Designation::whereIn('title', $titles)->whereDoesntHave('employees')->delete();
        });
    }
}
