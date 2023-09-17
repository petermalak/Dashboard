<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\MailsDataTable;
use App\Http\Controllers\Controller;
use App\Imports\EmailsImport;
use App\Jobs\SendEmailJob;
use App\Models\Category;
use App\Models\Email;
use App\Models\Mail;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class MailsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param MailsDataTable $mailsDataTable
     * @return Application|Factory|View
     */
    public function index(MailsDataTable $mailsDataTable)
    {
        return $mailsDataTable->render('admin.components.mail.datatable');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $mail = new Mail();
        $Allcategories = Category::all();
        $categories = [];
        $categories[0] = null;
        foreach ($Allcategories as $cat)
            $categories[$cat->id] = $cat->name;

        $Allemails = Email::all();
        $emails = [];
        $emails[0] = null;
        foreach ($Allemails as $e)
            $emails[$e->id] = $e->email;
//        dd($emails);
        return view('admin.components.mail.create', compact('mail', 'categories', 'emails'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $input = $request->all();

        if($input['datetime'] == null){
            $input['sent_time'] = Carbon::now();
            $input['scheduled'] = 0;
        }
        else{
            $input['sent_time'] = Carbon::parse($input['datetime']);;
            $input['scheduled'] = 1;
        }

        $validator = Validator::make($input, [
            'emails' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->route('mails.create')->withErrors($validator)->withInput();
        }
        $input['sender'] = env('MAIL_FROM_ADDRESS');
        $input['receiver'] = $input['emails'];
        $input['receiver'] = json_encode($input['receiver']);
        Mail::create($input);

        $details = [
            'message' => $input['message'],
            'subject' => $input['subject'],
            'receiver' => json_decode($input['receiver'], true)
        ];

        if ($input['scheduled'] == 0) {
            $job = (new SendEmailJob($details))->delay(0);
        } else {
            $date = strtotime($input['datetime']);
            $job = (new SendEmailJob($details))->delay(Carbon::parse($date));
        }
        dispatch($job);
        return redirect()->route('mails.index')->with(['success' => 'Mail ' . __("messages.add")]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $mail = Mail::findorfail($id);
        return view('admin.components.mail.edit', compact('mail'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();
        $mail = Mail::find($id);
        $mail->update($input);
        return redirect()->route('mails.index')->with(['success' => 'Mail ' . __("messages.update")]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        $mail = Mail::findOrFail($id);
        $mail->delete();
        return redirect()->route('mails.index')->with(['success' => 'Mail ' . __("messages.delete")]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function import(Request $request): RedirectResponse
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'file' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->route('mails.importView')->withErrors($validator)->withInput();
        }
        $temp = Excel::toArray(new EmailsImport, $input['file']);
        $rows = $temp[0];
        $emails_id = [];
        foreach ($rows as $row) {
            if ($row['email'] !== null && $row['email'] !== '') {
                $row['category'] = Category::firstOrCreate(['name' => strtolower($row['category'])]);
                $email = Email::where('email', $row['email'])->first();
                if ($email != null) {
                    if (!$email->categories->contains($row['category']->id)) {
                        $email->categories()->attach($row['category']);
                    }
                    $emails_id[] = $email->email;
                } else {
                    $category = $row['category'];
                    unset($row['category']);
                    $email = Email::firstOrCreate($row);
                    $email->categories()->attach($category);
                    $emails_id[] = $email->email;
                }
            }
        }
//        $emails_id = array_unique($emails_id);
//        $input['receiver'] = $emails_id;
//        $input['sender'] = 'admin@admin.com';
//        if ($input['datetime'] != null) {
//            $input['datetime'] = date('Y-m-d', strtotime($input['datetime']));
//        }
//        $validator = Validator::make($input, Mail::$cast);
//        if ($validator->fails()) {
//            return redirect()->route('mails.importView')->withErrors($validator)->withInput();
//        }
//        $input['receiver'] = json_encode($input['receiver']);
//        Mail::create($input);
//
//        $details = [
//            'message' => $input['message'],
//            'subject' => $input['subject'],
//            'receiver' => json_decode($input['receiver'], true)
//        ];
//
//        if ($input['datetime'] == null) {
//            $job = (new SendEmailJob($details))->delay(0);
//        } else {
//            $date = strtotime($input['datetime']);
//            $job = (new SendEmailJob($details))->delay($date - Carbon::now()->addHours(2)->timestamp);
//        }
//        dispatch($job);

        return redirect()->route('emails.index')->with(['success' => 'Email/s ' . __("messages.add")]);
    }

    /**
     * @return Application|Factory|View
     */
    public function importView()
    {
        return view('admin.components.mail.import');
    }
}
