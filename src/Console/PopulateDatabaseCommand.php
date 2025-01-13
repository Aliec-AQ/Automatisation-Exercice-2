<?php

namespace App\Console;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use Illuminate\Support\Facades\Schema;
use Slim\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Faker\Factory as Faker;

class PopulateDatabaseCommand extends Command
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('db:populate');
        $this->setDescription('Populate database');
    }

    protected function execute(InputInterface $input, OutputInterface $output ): int
    {
        $output->writeln('Populate database...');

        /** @var \Illuminate\Database\Capsule\Manager $db */
        $db = $this->app->getContainer()->get('db');

        $db->getConnection()->statement("SET FOREIGN_KEY_CHECKS=0");
        $db->getConnection()->statement("TRUNCATE `employees`");
        $db->getConnection()->statement("TRUNCATE `offices`");
        $db->getConnection()->statement("TRUNCATE `companies`");
        $db->getConnection()->statement("SET FOREIGN_KEY_CHECKS=1");

        $faker = Faker::create();

        $companies = [];
        for ($i = 1; $i <= rand(2, 4); $i++) {
            $companies[] = [
                'id' => $i,
                'name' => $faker->company,
                'phone' => $faker->phoneNumber,
                'email' => $faker->companyEmail,
                'website' => $faker->url,
                'image' => "https://picsum.photos/id/{$faker->numberBetween(0, 1000)}/800/600",
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
                'head_office_id' => null
            ];
        }
        $db->table('companies')->insert($companies);

        $offices = [];
        $officeId = 1;
        foreach ($companies as $company) {
            for ($j = 1; $j <= rand(2, 3); $j++) {
                $offices[] = [
                    'id' => $officeId,
                    'name' => $faker->city . ' Office',
                    'address' => $faker->address,
                    'city' => $faker->city,
                    'zip_code' => $faker->postcode,
                    'country' => $faker->country,
                    'email' => $faker->companyEmail,
                    'phone' => $faker->phoneNumber,
                    'company_id' => $company['id'],
                    'created_at' => new \DateTime(),
                    'updated_at' => new \DateTime(),
                ];
                $officeId++;
            }
        }
        $db->table('offices')->insert($offices);

        $employees = [];
        for ($k = 1; $k <= 10; $k++) {
            $employees[] = [
                'id' => $k,
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'office_id' => rand(1, $officeId - 1),
                'email' => $faker->email,
                'phone' => $faker->phoneNumber,
                'job_title' => $faker->jobTitle,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ];
        }
        $db->table('employees')->insert($employees);

        foreach ($companies as $company) {
            $headOfficeId = $offices[array_rand(array_filter($offices, fn($office) => $office['company_id'] === $company['id']))]['id'];
            $db->table('companies')->where('id', $company['id'])->update(['head_office_id' => $headOfficeId]);
        }

        $output->writeln('Database created successfully!');
        return 0;
    }
}