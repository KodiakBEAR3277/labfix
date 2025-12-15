<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $itUser = User::where('email', 'it@labfix.com')->first();
        
        if (!$itUser) {
            $this->command->warn('IT user not found. Please run UserSeeder first.');
            return;
        }

        $articles = [
            [
                'title' => 'Computer won\'t turn on - Troubleshooting steps',
                'category' => 'hardware',
                'content' => "If your computer won't turn on, follow these troubleshooting steps:\n\n1. Check the power cable connection\nMake sure the power cable is securely connected to both the computer and the power outlet.\n\n2. Verify the power outlet is working\nTest the outlet with another device to ensure it's providing power.\n\n3. Check the power button\nEnsure you're pressing the correct power button and that it's not stuck.\n\n4. Listen for POST beep\nWhen you press the power button, listen for a beep sound from the computer.\n\n5. Check the monitor connection\nIf the computer is on but the screen is black, verify the monitor cable is connected properly.\n\nIf none of these steps resolve the issue, please submit a support ticket.",
                'status' => 'published',
                'views' => 1245,
                'helpful_count' => 95,
                'not_helpful_count' => 5,
            ],
            [
                'title' => 'How to fix "No Internet Connection" error',
                'category' => 'network',
                'content' => "Experiencing internet connectivity issues? Try these solutions:\n\n1. Check your network cable\nEnsure the Ethernet cable is securely plugged into your computer.\n\n2. Restart your computer\nA simple restart can often resolve network issues.\n\n3. Check if others have internet\nVerify if the issue is affecting only your computer or the entire lab.\n\n4. Disable and re-enable network adapter\nGo to Network Settings and toggle your network adapter off and on.\n\n5. Flush DNS cache\nOpen Command Prompt and type: ipconfig /flushdns\n\nIf the problem persists, contact IT support for assistance.",
                'status' => 'published',
                'views' => 987,
                'helpful_count' => 92,
                'not_helpful_count' => 8,
            ],
            [
                'title' => 'Software installation fails - Common solutions',
                'category' => 'software',
                'content' => "Having trouble installing software? Here are common solutions:\n\n1. Check system requirements\nVerify that your computer meets the minimum requirements for the software.\n\n2. Run as administrator\nRight-click the installer and select 'Run as administrator'.\n\n3. Disable antivirus temporarily\nSome antivirus software can block installations.\n\n4. Clear temporary files\nDelete files in C:\\Windows\\Temp and C:\\Users\\YourUsername\\AppData\\Local\\Temp\n\n5. Check available disk space\nEnsure you have enough free space for the installation.\n\n6. Download installer again\nThe downloaded file might be corrupted.\n\nIf installation still fails, submit a support ticket with the error message.",
                'status' => 'published',
                'views' => 856,
                'helpful_count' => 88,
                'not_helpful_count' => 12,
            ],
            [
                'title' => 'Keyboard or mouse not working properly',
                'category' => 'peripherals',
                'content' => "If your keyboard or mouse isn't working:\n\n1. Check the connection\nFor wired devices, ensure the USB cable is properly connected.\nFor wireless devices, check if they're paired and have batteries.\n\n2. Try a different USB port\nSome ports may not be working properly.\n\n3. Restart your computer\nThis can resolve driver issues.\n\n4. Test with another device\nTry the keyboard/mouse on another computer to see if it's a hardware issue.\n\n5. Check for physical damage\nLook for visible damage to the cable or device.\n\n6. Update drivers\nGo to Device Manager and update the keyboard/mouse drivers.\n\nIf the issue continues, report it for a replacement.",
                'status' => 'published',
                'views' => 734,
                'helpful_count' => 90,
                'not_helpful_count' => 10,
            ],
            [
                'title' => 'Monitor display issues and black screen problems',
                'category' => 'display',
                'content' => "Experiencing monitor display issues? Follow these steps:\n\n1. Check cable connections\nEnsure both ends of the video cable (HDMI/VGA/DisplayPort) are secure.\n\n2. Verify monitor power\nCheck if the monitor's power LED is on.\n\n3. Test with another monitor\nConnect a different monitor to see if the issue is with the monitor or computer.\n\n4. Check input source\nPress the monitor's input/source button to ensure it's on the correct input.\n\n5. Adjust brightness\nThe monitor might be set to minimum brightness.\n\n6. Check graphics card\nEnsure the graphics card is properly seated if you have a desktop.\n\nFor persistent issues, submit a support ticket.",
                'status' => 'published',
                'views' => 612,
                'helpful_count' => 85,
                'not_helpful_count' => 15,
            ],
        ];

        foreach ($articles as $articleData) {
            Article::create([
                'author_id' => $itUser->id,
                'published_at' => now()->subDays(rand(1, 30)),
                ...$articleData,
            ]);
        }

        $this->command->info('Sample articles created successfully!');
    }
}