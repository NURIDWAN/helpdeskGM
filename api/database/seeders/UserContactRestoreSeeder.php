<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserContactRestoreSeeder extends Seeder
{
    /**
     * Restore phone_number and telegram_chat_id from SQL backup.
     * Lookup berdasarkan email agar tidak bergantung pada ID.
     * Force update - overwrite data yang ada.
     */
    public function run(): void
    {
        // Format: email => [phone_number, telegram_chat_id]
        $contacts = [
            'superadmin@gmail.com'           => ['081234567890', '8257927197'],
            'admin@gmail.com'                => ['081234567891', null],
            'staff@gmail.com'                => ['081234567892', null],
            'user@gmail.com'                 => ['081234567893', null],
            'vebby@hachigroup.id'            => ['081283356440', null],
            'jimmy@hachigroup.id'            => ['085863439140', null],
            'irwan@hachigroup.id'            => ['081240745073', '8507215523'],
            'budi-s@hachigroup.id'           => ['082130385454', '8264614080'],
            'acil@hachigroup.id'             => ['081221558860', '8599849491'],
            'wida@hachigroup.id'             => ['089516247814', null],
            'hw@hachigroup.id'               => ['087771776038', null],
            'djoyo@hachigroup.id'            => ['081330517878', null],
            'ibnu@hachigroup.id'             => ['085715166007', null],
            'fauzi@hachigroup.id'            => ['08990070715', '5374278610'],
            'wahyu-e@hachigroup.id'          => ['081398107473', '7901445889'],
            'm-fajar@hachigroup.id'          => ['081343159409', null],
            'dalih@hachigroup.id'            => ['087883352931', null],
            'bili@hachigroup.id'             => ['089696541030', '6439964697'],
            'deni-s@hachigroup.id'           => ['082214081449', '6371373546'],
            'rapip@hachigroup.id'            => ['085870555075', null],
            'andika@hachigroup.id'           => ['089507001195', '8028348169'],
            'firman@hachigroup.id'           => ['089636187652', '1261563623'],
            'daniel@hachigroup.id'           => ['085226444135', '7728482901'],
            'khadaffi@hachigroup.id'         => ['081584322660', null],
            'wahyuokta@hachigroup.id'        => ['082334817677', null],
            'anggi@hachigroup.id'            => ['081293107628', null],
            'merdian@hachigroup.id'          => ['0816244320', '487194539'],
            'ob-cibubur@hachigroup.id'       => ['081197116701', '8419090746'],
            'sh-pajajaran@hachigroup.id'     => ['087872023967', '6085997229'],
            'sh-margonda@hachigroup.id'      => ['083877462559', '1352812729'],
            'hg-puri@hachigroup.id'          => ['081197116727', null],
            'mk-puri@hachigroup.id'          => ['081818184078', '1214490340'],
            'sh-bekasi@hachigroup.id'        => ['08170186945', '6017678223'],
            'sh-bassura@hachigroup.id'       => ['081112549620', '7225871051'],
            'ob-jgc@hachigroup.id'           => ['081112549622', '8402693797'],
            'sh-kuncit@hachigroup.id'        => ['081119092537', '7892563610'],
            'hg-sunter@hachigroup.id'        => ['081112549616', null],
            'hg-bonsir@hachigroup.id'        => ['081112549606', '5900132916'],
            'sh-smb@hachigroup.id'           => ['082118029196', '8408699803'],
            'sh_cilaki@hachigroup.id'        => ['081322003947', '8166905601'],
            'daisuki-kebayoran@hachigroup.id' => ['0881025804964', null],
            'sh-makassar@hachigroup.id'      => ['08563788583', null],
            'hg-sutami@hachigroup.id'        => ['082258254838', null],
            'hg-kg@hachigroup.id'            => ['081112549632', null],
            'sh-gs@hachigroup.id'            => ['081197116724', null],
            'hg-alsut@hachigroup.id'         => ['081211116059', '8297276933'],
            'hgn-alsut@hachigroup.id'        => ['081112549617', null],
            'hgn-ampera@hachigroup.id'       => ['081112549630', null],
            'hg-ampera@hachigroup.id'        => ['081318192062', '7623062663'],
            'hg-tb@hachigroup.id'            => ['081197116721', '8175595477'],
            'hg-bintaro@hachigroup.id'       => ['081112549610', '8547797575'],
            'shlb@hachigroup.id'             => ['081318192061', null],
            'hg-gatsu@hachigroup.id'         => ['0811977116704', '6009336382'],
            'sh-cibubur@hachigroup.id'       => ['081197116702', null],
            'sopyan@hachigroup.id'           => ['087877555766', null],
            'hg-cakung@hachigroup.id'        => ['081112549635', null],
            'iqbal@hachigroup.id'            => ['082173523922', '7387876958'],
            'sungkono@hachigroup.id'         => ['085287602277', null],
            'dc-kuncit@hachigroup.id'        => ['085723140956', null],
            'MasYo@hachigroup.di'            => ['081112549639', null],
            'hanif@hachigroup.id'            => ['085892483655', '1315522715'],
            'alam@hachigroup.id'             => ['088983093624', null],
            'HG-Yasmin@hachigroup.id'        => ['085735530392', null],
        ];

        $updated = 0;
        $notFound = [];

        foreach ($contacts as $email => [$phone, $telegramId]) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

            if (!$user) {
                $notFound[] = $email;
                continue;
            }

            $user->phone_number = $phone;
            $user->telegram_chat_id = $telegramId;
            $user->save();
            $updated++;
        }

        $this->command->info("Updated contact info for {$updated} users.");

        if (count($notFound) > 0) {
            $this->command->warn("Users not found by email: " . implode(', ', $notFound));
        }
    }
}
