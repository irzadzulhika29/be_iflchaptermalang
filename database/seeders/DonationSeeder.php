<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Campaign;
use App\Models\Donation;

class DonationSeeder extends Seeder
{
    private function updateCurrenDonation($campaign, $donation_amount)
    {
      $campaign->update(['current_donation' => $campaign->current_donation + $donation_amount]);
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminId = AdminSeeder::getId();
        $bisMarId = BisMarSeeder::getId();
        $copyWriterId = CopyWriterSeeder::getId();
        $userId = UserSeeder::getId();
        $campaignIds = CampaignSeeder::getCampaignId();
        $campaign1 = Campaign::find($campaignIds[0]);
        $campaign2 = Campaign::find($campaignIds[1]);

        $donation1 = Donation::factory()->create([
          'campaign_id' => $campaign1,
          'user_id' => $adminId,
          'anonymous' => 0,
          'donation_amount' => 7000.00,
          'status' => 'paid',
        ]);
        $this->updateCurrenDonation($campaign1, $donation1->donation_amount);

        $donation2 = Donation::factory()->create([
          'campaign_id' => $campaign1,
          'user_id' => $bisMarId,
          'anonymous' => 0,
          'donation_amount' => 8000.00,
          'status' => 'paid',
        ]);
        $this->updateCurrenDonation($campaign1, $donation2->donation_amount);

        $donation3 = Donation::factory()->create([
          'campaign_id' => $campaign1,
          'user_id' => $copyWriterId,
          'anonymous' => 0,
          'donation_amount' => 9000.00,
          'status' => 'paid',
        ]);
        $this->updateCurrenDonation($campaign1, $donation3->donation_amount);

        $donation4 = Donation::factory()->create([
          'campaign_id' => $campaign1,
          'user_id' => $userId,
          'anonymous' => 0,
          'donation_amount' => 10000.00,
          'status' => 'paid',
        ]);
        $this->updateCurrenDonation($campaign1, $donation4->donation_amount);
        
        $donation5 = Donation::factory()->create([
          'campaign_id' => $campaign2,
          'user_id' => $adminId,
          'anonymous' => 0,
          'donation_amount' => 7000.00,
          'status' => 'paid',
        ]);
        $this->updateCurrenDonation($campaign2, $donation5->donation_amount);

        $donation6 = Donation::factory()->create([
          'campaign_id' => $campaign2,
          'user_id' => $bisMarId,
          'anonymous' => 0,
          'donation_amount' => 8000.00,
          'status' => 'paid',
        ]);
        $this->updateCurrenDonation($campaign2, $donation6->donation_amount);

        $donation7 = Donation::factory()->create([
          'campaign_id' => $campaign2,
          'user_id' => $copyWriterId,
          'anonymous' => 0,
          'donation_amount' => 9000.00,
          'status' => 'paid',
        ]);
        $this->updateCurrenDonation($campaign2, $donation7->donation_amount);

        $donation8 = Donation::factory()->create([
          'campaign_id' => $campaign2,
          'user_id' => $userId,
          'anonymous' => 0,
          'donation_amount' => 10000.00,
          'status' => 'paid',
        ]);
        $this->updateCurrenDonation($campaign2, $donation8->donation_amount);
    }
}
