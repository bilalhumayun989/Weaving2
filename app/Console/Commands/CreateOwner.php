<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
class CreateOwner extends Command
{
    protected $signature = 'erp:create-owner';
    protected $description = 'Securely create the initial ERP administrator account';
    public function handle(): int
    {
        if(User::where('role','admin')->exists()) {
            $this->error('An administrator already exists. Add staff through Users & Audit.');
            return self::FAILURE;
        }
        $name=(string)$this->ask('Owner name');
        $email=(string)$this->ask('Email address');
        $password=(string)$this->secret('Password (12+ chars, letters & numbers)');
        $confirm=(string)$this->secret('Confirm password');
        if(trim($name)==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || User::where('email',$email)->exists() ||
            strlen($password)<12 || !preg_match('/[a-zA-Z]/',$password) || !preg_match('/[0-9]/',$password) || $password!==$confirm) {
            $this->error('Invalid name, email or password. No account created.');
            return self::FAILURE;
        }
        $user=new User();
        $user->name=$name; $user->email=$email; $user->password=Hash::make($password); $user->role='admin';
        $user->save();
        $this->info('Owner account created. You can now sign in.');
        return self::SUCCESS;
    }
}
