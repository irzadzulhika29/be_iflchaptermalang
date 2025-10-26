<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminNotice;
use App\Models\User;
use Throwable;

class SendActivationEmail implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $user;
  protected $tries = 5;
  protected $retryAfter = 0;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(User $user)
  {
    $this->user = $user;
  }

  private function sendAdminNotification(Throwable $exception)
  {
    $subject = "User Email Verification Failed";
    $errorMessage = $exception->getMessage();
    $userEmail = $this->user->email;
    $admin = "iflchaptermalang@gmail.com";
    
    $message = "Failed to send activation email to user with email : " . $userEmail;

    Mail::to($admin)->send(new AdminNotice($subject, $message, $errorMessage, $userEmail));
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    $this->user->sendEmailVerificationNotification();
  }

  public function failed(Throwable $exception = null)
  {
      $this->sendAdminNotification($exception);
  }
}
