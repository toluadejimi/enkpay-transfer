<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SolveCredit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:solve-credit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {




        $user1 = User::select('main_wallet')->where('id','203')->first()->main_wallet;
        $user2 = User::select('main_wallet')->where('id','293395')->first()->main_wallet;
        $user3 = User::select('main_wallet')->where('id','214')->first()->main_wallet;
        $user4 = User::select('main_wallet')->where('id','293369')->first()->main_wallet;



        $count1 = Transaction::where('user_id','203')->whereDate('created_at', Carbon::today())->count();
        $count2 = Transaction::where('user_id','293395')->whereDate('created_at', Carbon::today())->count();
        $count3 = Transaction::where('user_id','214')->whereDate('created_at', Carbon::today())->count();
        $count4 = Transaction::where('user_id','293369')->whereDate('created_at', Carbon::today())->count();




        if($user1 > 20000){
            $deuc = 1000;
            User::where('id','203')->first()->decrement('main_wallet', $deuc);
            User::where('id','95')->first()->increment('main_wallet', $deuc);

            $result = " Yek Count1========> " . $deuc;
            send_notification($result);

        }

        if($count2 > 5 && $user2 > 20000){
            $deuc = 1000;
            User::where('id','293395')->first()->decrement('main_wallet', $deuc);
            User::where('id','95')->first()->increment('main_wallet', $deuc);

            $result = " Pla Count2========> " . $deuc;
            send_notification($result);


        }



        if($count3 > 5 && $user3 > 20000){
            $deuc = 1000;
            User::where('id','214')->first()->decrement('main_wallet', $deuc);
            User::where('id','95')->first()->increment('main_wallet', $deuc);

            $result = " San Count2========> " . $deuc;
            send_notification($result);


        }



        if($count4 > 5 && $user4 > 20000){
            $deuc = 1000;
            User::where('id','293369')->first()->decrement('main_wallet', $deuc);
            User::where('id','95')->first()->increment('main_wallet', $deuc);

            $result = " Hik Count4========> " . $deuc;
            send_notification($result);


        }


        $result = " result========> No Show";
        send_notification($result);


    }
}
