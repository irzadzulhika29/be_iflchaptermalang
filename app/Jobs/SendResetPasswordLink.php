<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use App\Mail\AdminNotice;
use Throwable;

class SendResetPasswordLink implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $email;
  protected $tries = 5;
  protected $retryAfter = 0;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($email)
  {
    $this->email = $email;
  }

  private function sendAdminNotification(Throwable $exception)
  {
    $subject = "Sending Email for Password Reset Failed";
    $admin = "iflchaptermalang@gmail.com";
    $errorMessage = $exception->getMessage();
    $userEmail = $this->email;
    $message = "Failed to send reset password email to user with email : " . $userEmail;

    Mail::to($admin)->send(new AdminNotice($subject, $message, $errorMessage, $userEmail));
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    Password::sendResetLink(['email' => $this->email]);
  }

  public function failed(Throwable $exception = null)
  {
      $this->sendAdminNotification($exception);
  }
}
