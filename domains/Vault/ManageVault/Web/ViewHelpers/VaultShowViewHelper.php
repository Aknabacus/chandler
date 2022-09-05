<?php

namespace App\Vault\ManageVault\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\ContactTask;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;

class VaultShowViewHelper
{
    public static function lastUpdatedContacts(Vault $vault): Collection
    {
        return $vault->contacts()
            ->orderBy('last_updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => $contact->avatar,
                    'url' => [
                        'show' => route('contact.show', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                        ]),
                    ],
                ];
            });
    }

    public static function upcomingReminders(Vault $vault, User $user): array
    {
        // this query is a bit long and tough to do, and it could surely
        // be optimized if I knew how to properly join queries
        // first we get all the users the vault
        $usersInVaultIds = $vault->users->pluck('id')->toArray();

        // then we get all the user notification channels for those users
        $userNotificationChannelIds = UserNotificationChannel::whereIn('user_id', $usersInVaultIds)
            ->select('id')
            ->get()
            ->unique('id')
            ->toArray();

        // then we get all the contact reminders scheduled for those channels
        $currentDate = Carbon::now()->copy();
        $currentDate->second = 0;

        $contactRemindersScheduled = DB::table('contact_reminder_scheduled')
            ->whereDate('scheduled_at', '<=', $currentDate->addDays(30))
            ->where('triggered_at', null)
            ->whereIn('user_notification_channel_id', $userNotificationChannelIds)
            ->get();

        // finally, we get all the details about those reminders
        // yeah, it's painful
        $remindersCollection = collect();
        foreach ($contactRemindersScheduled as $contactReminderScheduled) {
            $reminder = ContactReminder::where('id', $contactReminderScheduled->contact_reminder_id)->with('contact')->first();
            $contact = $reminder->contact;

            if ($contact->vault_id !== $vault->id) {
                continue;
            }

            $scheduledAtDate = Carbon::createFromFormat('Y-m-d H:i:s', $contactReminderScheduled->scheduled_at);

            $remindersCollection->push([
                'id' => $reminder->id,
                'label' => $reminder->label,
                'scheduled_at' => DateHelper::format($scheduledAtDate, $user),
                'contact' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => $contact->avatar,
                    'url' => [
                        'show' => route('contact.show', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                        ]),
                    ],
                ],
            ]);
        }

        return [
            'reminders' => $remindersCollection,
            'url' => [
                'index' => route('vault.reminder.index', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }

    public static function favorites(Vault $vault, User $user): Collection
    {
        $favorites = DB::table('contact_vault_user')
            ->where('vault_id', $vault->id)
            ->where('user_id', $user->id)
            ->where('is_favorite', true)
            ->select('contact_id')
            ->get()
            ->pluck('contact_id')
            ->toArray();

        return Contact::whereIn('id', $favorites)
            ->get()
            ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => $contact->avatar,
                    'url' => [
                        'show' => route('contact.show', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                        ]),
                    ],
                ];
            });
    }

    public static function dueTasks(Vault $vault, User $user): array
    {
        $contactIds = $vault->contacts()->select('id')->get()->toArray();
        $tasks = DB::table('contact_tasks')
            ->where('completed', false)
            ->whereIn('contact_id', $contactIds)
            ->where('due_at', '<=', Carbon::now()->addDays(30))
            ->orderBy('due_at', 'asc')
            ->get();

        $tasksCollection = $tasks
            ->map(function ($task) use ($user) {
                $task = ContactTask::find($task->id);
                $contact = $task->contact;

                return [
                    'id' => $task->id,
                    'label' => $task->label,
                    'description' => $task->description,
                    'completed' => $task->completed,
                    'completed_at' => $task->completed_at ? DateHelper::format($task->completed_at, $user) : null,
                    'due_at' => $task->due_at ? DateHelper::format($task->due_at, $user) : null,
                    'due_at_late' => optional($task->due_at)->isPast() ?? false,
                    'url' => [
                        'toggle' => route('contact.task.toggle', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                            'task' => $task->id,
                        ]),
                    ],
                    'contact' => [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'avatar' => $contact->avatar,
                        'url' => [
                            'show' => route('contact.show', [
                                'vault' => $contact->vault_id,
                                'contact' => $contact->id,
                            ]),
                        ],
                    ],
                ];
            });

        return [
            'tasks' => $tasksCollection,
            'url' => [
                'index' => route('vault.tasks.index', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }
}
