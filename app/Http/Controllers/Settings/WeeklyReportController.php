<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateWeeklyReportRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class WeeklyReportController extends Controller
{
    /**
     * Update the user's weekly report preferences.
     */
    public function update(UpdateWeeklyReportRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return Redirect::back()->with('success', 'Weekly report preferences updated successfully.');
    }

    /**
     * Unsubscribe the user from weekly reports via signed link.
     */
    public function unsubscribe(Request $request, User $user): View
    {
        $user->update([
            'weekly_report_enabled' => false,
        ]);

        return view('emails.weekly-report-unsubscribed', [
            'user' => $user,
        ]);
    }
}
