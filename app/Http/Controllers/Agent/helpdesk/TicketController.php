<?php
namespace App\Http\Controllers\Agent\helpdesk;

// controllers
use App\Http\Controllers\Common\FileuploadController;
use App\Http\Controllers\Common\NotificationController as Notify;
use App\Http\Controllers\Common\PhpMailController;
use App\Http\Controllers\Controller;
// requests
use App\Http\Requests\helpdesk\CreateTicketRequest;
use App\Http\Requests\helpdesk\TicketRequest;
// models
use App\Model\helpdesk\Agent\Department;
use App\Model\helpdesk\Agent\Teams;
use App\Model\helpdesk\Email\Emails;
use App\Model\helpdesk\Form\Fields;
use App\Model\helpdesk\Manage\Help_topic;
use App\Model\helpdesk\Manage\Sla_plan;
use App\Model\helpdesk\Notification\Notification;
use App\Model\helpdesk\Notification\UserNotification;
use App\Model\helpdesk\Settings\Alert;
use App\Model\helpdesk\Settings\CommonSettings;
use App\Model\helpdesk\Settings\Company;
use App\Model\helpdesk\Settings\Email;
use App\Model\helpdesk\Settings\System;
use App\Model\helpdesk\Ticket\Ticket_attachments;
use App\Model\helpdesk\Ticket\Ticket_Collaborator;
use App\Model\helpdesk\Ticket\Ticket_Form_Data;
use App\Model\helpdesk\Ticket\Ticket_Priority;
use App\Model\helpdesk\Ticket\Ticket_source;
use App\Model\helpdesk\Ticket\Ticket_Status;
use App\Model\helpdesk\Ticket\Ticket_Thread;
use App\Model\helpdesk\Ticket\Tickets;
use App\Model\helpdesk\Utility\CountryCode;
use App\Model\helpdesk\Utility\Date_time_format;
use App\Model\helpdesk\Utility\Timezones;
use App\User;
use Auth;
use DB;
use Exception;
use GeoIP;
// classes
use Hash;
use Illuminate\Http\Request;
use Illuminate\support\Collection;
use Input;
use Lang;
use Mail;
use PDF;

/**
 * TicketController.
 *
 * @author      Ladybird <info@ladybirdweb.com>
 */
class TicketController extends Controller
{

    protected $ticket_policy;

    /**
     * Create a new controller instance.
     *
     * @return type response
     */
    public function __construct(PhpMailController $PhpMailController, Notify $NotificationController)
    {
        $this->PhpMailController = $PhpMailController;
        $this->NotificationController = $NotificationController;
        $this->middleware('auth');
        $this->ticket_policy = new \App\Policies\TicketPolicy();
    }

    /**
     * Show the Inbox ticket list page.
     *
     * @return type response
     */
    public function inbox_ticket_list()
    {
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.ticket.inbox', compact('table'));
    }

    /**
     * Show the Open ticket list page.
     *
     * @return type response
     */
    public function open_ticket_list()
    {
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.ticket.open', compact('table'));
    }

    /**
     * Show the answered ticket list page.
     *
     * @return type response
     */
    public function answered_ticket_list()
    {
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.ticket.answered', compact('table'));
    }

    /**
     * Show the Myticket list page.
     *
     * @return type response
     */
    public function myticket_ticket_list()
    {
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.ticket.myticket', compact('table'));
    }

    /**
     * Show the Overdue ticket list page.
     *
     * @return type response
     */
    public function overdue_ticket_list()
    {
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.ticket.overdue', compact('table'));
    }

    /**
     * Show the Open ticket list page.
     *
     * @return type response
     */
    public function dueTodayTicketlist()
    {
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.ticket.duetodayticket', compact('table'));
    }

    /**
     * Show the Closed ticket list page.
     *
     * @return type response
     */
    public function closed_ticket_list()
    {
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.ticket.closed', compact('table'));
    }

    /**
     * Show the ticket list page.
     *
     * @return type response
     */
    public function assigned_ticket_list()
    {
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.ticket.assigned', compact('table'));
    }

    /**
     * Show the New ticket page.
     *
     * @return type response
     */
    public function newticket(CountryCode $code)
    {
        if (!$this->ticket_policy->create()) {
            return redirect('dashboard')->with('fails', Lang::get('lang.permission-denied'));
        }
        return view('themes.default1.agent.helpdesk.ticket.new');
    }

    /**
     * Save the data of new ticket and show the New ticket page with result.
     *
     * @param type CreateTicketRequest $request
     *
     * @return type response
     */
    public function post_newticket(Request $request, CountryCode $code, $api = false)
    {
        if (!$this->ticket_policy->create()) {
            if ($api) {
                return response()->json(['message' => 'permission denied'], 403);
            }
            return redirect('dashboard')->with('fails', 'Permission denied');
        }
        try {
            $email = null;
            $fullname = null;
            $mobile_number = null;
            $phonecode = null;
            $default_values = ['Requester', 'Requester_email', 'Requester_name', 'Requester_mobile',
                'Requester_mobile', 'Requester_code', 'Group', 'Assigned', 'Subject', 'Description',
                'Priority', 'Type', 'Status', 'attachment', 'inline', 'email', 'first_name', 'last_name', 'mobile', 'country_code'];
            $form_data = $request->except($default_values);
            $requester = $request->input('Requester');
            if ($request->has('Requester')) {
                $user = User::find($requester);
            }
            if ($request->has('api')) {
                $api = $request->input('api');
            }
            if ($request->has('Requester_email')) {
                $email = $request->input('Requester_email');
            } elseif ($user) {
                $email = $user->email;
            }
            if ($request->has('Requester_name')) {
                $fullname = $request->input('Requester_name');
            } elseif ($user) {
                $fullname = $user->first_name;
            }
            if ($request->has('Requester_mobile')) {
                $mobile_number = $request->input('Requester_mobile');
            } elseif ($user) {
                $mobile_number = $user->mobile;
            }
            if ($request->has('Requester_code')) {
                $phonecode = $request->input('Requester_code');
            } elseif ($user) {
                $phonecode = $user->country_code;
            }
            if ($request->has('Group')) {
                $helptopic = $request->input('Group');
                $help = Help_topic::where('id', '=', $helptopic)->first();
            } else {
                $help = Help_topic::first();
                $helptopic = $help->id;
            }
            if ($request->has('Assigned')) {
                $assignto = $request->input('Assigned');
            } else {
                $assignto = null;
            }
            if ($request->has('Subject')) {
                $subject = $request->input('Subject');
            } else {
                $subject = null;
            }
            if ($request->has('Description')) {
                $body = $request->input('Description');
            } else {
                $body = null;
            }
            if ($request->has('Priority')) {
                $priority = $request->input('Priority');
            } else {
                $priority = null;
            }
            if ($request->input('Status')) {
                $status = $request->input('Status');
            } else {
                $status = null;
            }
            $phone = "";
            $source = Ticket_source::where('name', '=', 'agent')->first();
            $headers = null;
            $auto_response = 0;
            $sla = "";
            $attach = $request->input('attachment');
            $inline = $request->input('inline');
            $result = $this->create_user($email, $fullname, $subject, $body, $phone, $phonecode, $mobile_number, $helptopic, $sla, $priority, $source->id, $headers, $help->department, $assignto, $form_data, $auto_response, $status, $attach, $inline);
            if ($result[1]) {
                $status = $this->checkUserVerificationStatus();
                if ($status == 1) {
                    if ($api != false) {
                        return response()->json(['success' => Lang::get('lang.Ticket-created-successfully')]);
                    }
                    return Redirect('newticket')->with('success', Lang::get('lang.Ticket-created-successfully'));
                } else {
                    if ($api != false) {
                        return response()->json(['success' => Lang::get('lang.Ticket-created-successfully')]);
                    }
                    return Redirect('newticket')->with('success', Lang::get('lang.Ticket-created-successfully2'));
                }
            } else {
                if ($api != false) {
                    return response()->json(['error' => Lang::get('lang.failed-to-create-user-tcket-as-mobile-has-been-taken')], 500);
                }
                return Redirect('newticket')->with('fails', Lang::get('lang.failed-to-create-user-tcket-as-mobile-has-been-taken'))->withInput($request->except('password'));
            }
        } catch (Exception $e) {
            dd($e);
            $api = true;
            if ($api != false) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return Redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Shows the ticket thread details.
     *
     * @param type $id
     *
     * @return type response
     */
    public function thread($id)
    {
        $tickets = Tickets::where('tickets.id', $id)
            ->select('tickets.id', 'ticket_number', 'tickets.user_id', 'tickets.assigned_to', 'source', 'dept_id', 'priority_id', 'sla', 'help_topic_id', 'status', 'tickets.created_at', 'tickets.duedate');
        if (!$tickets->first()) {
            return redirect()->back()->with('fails', \Lang::get('lang.invalid_attempt'));
        }
        $auth_agent = \Auth::user();
        $ticket_policy = new \App\Policies\TicketPolicy();
        if ($auth_agent->role == 'agent') {
            $dept = Department::where('id', '=', $auth_agent->primary_dpt)->first();
            $tickets = Tickets::where('id', '=', $id)->first();
            if ($tickets->dept_id == $dept->id) {
                $tickets = $tickets;
            } elseif ($tickets->assigned_to == $auth_agent->id) {
                $tickets = $tickets;
            } else {
                $tickets = null;
            }
            //            $tickets = $tickets->where('dept_id', '=', $dept->id)->orWhere('assigned_to', Auth::user()->id)->first();
            //            dd($tickets);
        } elseif ($auth_agent->role == 'admin') {
            $tickets = Tickets::where('id', '=', $id)->first();
        } elseif ($auth_agent->role == 'user') {
            $thread = Ticket_Thread::where('ticket_id', '=', $id)->first();
            $ticket_id = \Crypt::encrypt($id);
            return redirect()->route('check_ticket', compact('ticket_id'));
        }
        if ($tickets == null) {
            return redirect()->route('inbox.ticket')->with('fails', \Lang::get('lang.invalid_attempt'));
        }
        $avg = DB::table('ticket_thread')->where('ticket_id', '=', $id)->where('reply_rating', '!=', 0)->avg('reply_rating');
        $avg_rate = explode('.', $avg);
        $avg_rating = $avg_rate[0];
        $thread = Ticket_Thread::where('ticket_id', '=', $id)->first();
        $fileupload = new FileuploadController();
        $fileupload = $fileupload->file_upload_max_size();
        $max_size_in_bytes = $fileupload[0];
        $max_size_in_actual = $fileupload[1];
        $tickets_approval = Tickets::where('id', '=', $id)->first();
        return view('themes.default1.agent.helpdesk.ticket.timeline', compact('tickets', 'max_size_in_bytes', 'max_size_in_actual', 'tickets_approval'), compact('thread', 'avg_rating', 'ticket_policy'));
    }

    public function size()
    {
        $files = Input::file('attachment');
        if (!$files) {
            throw new \Exception('file size exceeded');
        }
        $size = 0;
        if (count($files) > 0) {
            foreach ($files as $file) {
                $size += $file->getSize();
            }
        }
        return $size;
    }

    public function error($e, $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $error = $e->getMessage();
            if (is_object($error)) {
                $error = $error->toArray();
            }
            return response()->json(compact('error'));
            //return $message;
        }
    }

    /**
     * Replying a ticket.
     *
     * @param type Ticket_Thread $thread
     * @param type TicketRequest $request
     *
     * @return type bool
     */
    public function reply(Request $request, $ticketid = "", $mail = true, $system_reply = true, $user_id = '')
    {
        $this->validate($request, [
            'content' => 'required',
        ], [
            'content.required' => 'Reply Content Required',
        ]);
        try {
            if (!$ticketid) {
                $ticketid = $request->input('ticket_id');
            }
            $body = $request->input('content');
            $email = $request->input('email');
            $inline = $request->input('inline');
            $attachment = $request->input('attachment');
            $source = source($ticketid);
            $form_data = $request->except('content', 'ticket_id', 'attachment', 'inline');
            \Event::fire(new \App\Events\ClientTicketFormPost($form_data, $email, $source));
            if ($system_reply == true && Auth::user()) {
                $user_id = Auth::user()->id;
            } else {
                $user_id = requester($ticketid);
                if ($user_id !== "") {
                    $user_id = $user_id;
                }
            }
            $this->saveReply($ticketid, $body, $user_id, $system_reply, $attachment, $inline, $mail);
        } catch (\Exception $e) {
            dd($e);
            $result = $e->getMessage();
            return response()->json(compact('result'), 500);
        }
        $result = ["success" => "Replyed successfully"];
        return response()->json(compact('result'));
    }

    public function saveReply($ticket_id, $body, $user_id, $system_reply, $attachment = [], $inline = [], $mail = true, $poster = 'support')
    {
        $ticket = $this->saveReplyTicket($ticket_id, $system_reply);
        $thread = $ticket->thread()->create([
            'ticket_id' => $ticket_id,
            'user_id' => $user_id,
            'poster' => $poster,
            'body' => $body,
        ]);
        $thread = $this->saveReplyAttachment($thread, $attachment, $inline);
        $this->replyNotification($ticket, $thread, $mail);
        return $thread;
    }

    public function saveReplyTicket($ticket_id, $system_reply)
    {
        $tickets = new Tickets();
        $ticket = $tickets->find($ticket_id);
        if (!$ticket) {
            throw new Exception('Invalid ticket number');
        }
        $ticket->isanswered = '1';
        $ticket->save();
        if ($system_reply == true) {
            if ($ticket->assigned_to == 0) {
                $ticket->assigned_to = Auth::user()->id;
                $ticket->save();
                $data = [
                    'id' => $ticket_id,
                ];
                \Event::fire('ticket-assignment', [$data]);
            }
            if ($ticket->statuses->name !== 'Open') {
                $this->open($ticket_id, $tickets);
            }
        }
        return $ticket;
    }

    public function replyNotification($ticket, $thread, $mail)
    {
        $request = new Request();
        $reply_content = $request->input('content');
        $ticketid = $ticket->id;
        $ticket_subject = title($ticketid);
        $requester = $ticket->user;
        $email = $requester->email;
        $ticket_number = $ticket->ticket_number;
        $username = $requester->first_name;
        if (!empty(Auth::user()->agent_sign)) {
            $agentsign = Auth::user()->agent_sign;
        } else {
            $agentsign = null;
        }
        // Event
        \Event::fire(new \App\Events\FaveoAfterReply($reply_content, $requester->mobile, $requester->country_code, $request, $ticket, $thread));
        if (Auth::user()) {
            $u_id = Auth::user()->first_name . ' ' . Auth::user()->last_name;
        } else {
            $u_id = $this->getAdmin()->first_name . ' ' . $this->getAdmin()->last_name;
        }
        $data = [
            "ticket_id" => $ticketid,
            'u_id' => $u_id,
            'body' => $reply_content,
        ];
        if (!$request->has('do-not-send')) {
            \Event::fire('Reply-Ticket', array($data));
        }
        $line = "---Reply above this line---<br><br>";
        $collaborators = Ticket_Collaborator::where('ticket_id', '=', $ticketid)->get();
        if (!$email) {
            $mail = false;
        }
        if ($thread->poster == 'client') {
            $key = 'reply_notification_alert';
        } else {
            $key = 'reply_alert';
        }
        $notifications[] = [
            $key => [
                'from' => $this->PhpMailController->mailfrom('1', $ticket->dept_id),
                'message' => ['subject' => $ticket_subject . '[#' . $ticket_number . ']',
                    'body' => utf8_encode($line . $thread->purify(false)),
                    'scenario' => 'ticket-reply',
                    'attachments' => $thread->attach()->get()->toArray()
                ],
                'variable' => ['ticket_number' => $ticket_number,
                    'user' => $username, 'agent_sign' => $agentsign],
                'ticketid' => $ticket->id,
                'send_mail' => $mail,
                'model' => $thread,
                'thread' => $thread,
            ],
        ];
        $notification = new Notifications\NotificationController();
        $notification->setDetails($notifications);
    }

    /**
     * Ticket edit and save ticket data.
     *
     * @param type $ticket_id
     * @param type Ticket_Thread $thread
     *
     * @return type bool
     */
    public function ticketEditPost($ticket_id, Ticket_Thread $thread, Tickets $ticket)
    {
        if (!$this->ticket_policy->edit()) {
            $response = ['message' => 'permission denied'];
            return response()->json(compact('response'), 403);
        }
        try {
            $ticket = $tickets->where('id', '=', $ticket_id)->first();
            $tkt_dept = $ticket->dept_id;
            // dd($tkt_dept->dept_id);
            $priority = Input::get('ticket_priority');
            $ticket->help_topic_id = Input::get('help_topic');
            // $dept = Help_topic::select('department')->where('id', '=', $ticket->help_topic_id)->first();
            // $dept = $tkt_dept->dept_id;
            $sla = $this->getSla(Input::get('ticket_type'), $ticket->user_id, $tkt_dept, Input::get('ticket_source'), $priority);
            $priority_id = $this->getPriority($priority, $sla);
            $ticket->sla = $sla;
            $ticket->source = Input::get('ticket_source');
            $ticket->priority_id = $priority_id;
            $ticket->type = Input::get('ticket_type');
            $ticket->dept_id = $tkt_dept;
            $ticket = $this->updateOverdue($ticket, $sla);
            $ticket->save();
            $threads = $thread->where('ticket_id', '=', $ticket_id)->first();
            $threads->title = Input::get('subject');
            $threads->save();
            \Event::fire('notification', [$threads]);
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
            return response()->json(compact('result'), 500);
        }
        $result = ["success" => "Edited successfully"];
        return response()->json(compact('result'));
    }

    /**
     * Print Ticket Details.
     *
     * @param type $id
     *
     * @return type respponse
     */
    public function ticket_print($id)
    {
        $tickets = Tickets::
        leftJoin('ticket_thread', function ($join) {
            $join->on('tickets.id', '=', 'ticket_thread.ticket_id')
                ->whereNotNull('ticket_thread.title');
        })
            ->leftJoin('department', 'tickets.dept_id', '=', 'department.id')
            ->leftJoin('help_topic', 'tickets.help_topic_id', '=', 'help_topic.id')
            ->where('tickets.id', '=', $id)
            ->select('ticket_thread.title', 'tickets.ticket_number', 'department.name as department', 'help_topic.topic as helptopic')
            ->first();
        $ticket = Tickets::where('tickets.id', '=', $id)->first();
        $html = view('themes.default1.agent.helpdesk.ticket.pdf', compact('id', 'ticket', 'tickets'))->render();
        $html1 = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        return PDF::load($html1)->show();
    }

    /**
     * Generates Ticket Number.
     *
     * @param type $ticket_number
     *
     * @return type integer
     */
    public function ticketNumberold($ticket_number)
    {
        $number = $ticket_number;
        $number = explode('-', $number);
        $number1 = $number[0];
        if ($number1 == 'ZZZZ') {
            $number1 = 'AAAA';
        }
        $number2 = $number[1];
        if ($number2 == '9999') {
            $number2 = '0000';
        }
        $number3 = $number[2];
        if ($number3 == '9999999') {
            $number3 = '0000000';
        }
        $number1++;
        $number2++;
        $number3++;
        $number2 = sprintf('%04s', $number2);
        $number3 = sprintf('%07s', $number3);
        $array = [$number1, $number2, $number3];
        $number = implode('-', $array);
        return $number;
    }

    public function ticketNumber($ticket_number)
    {
        $ticket_settings = new \App\Model\helpdesk\Settings\Ticket();
        $setting = $ticket_settings->find(1);
        $format = $setting->num_format;
        $type = $setting->num_sequence;
        $number = $this->getNumber($ticket_number, $type, $format);
        return $number;
    }

    public function getNumber($ticket_number, $type, $format, $check = true)
    {
        $force = false;
        if ($check === false) {
            $force = true;
        }
        $controller = new \App\Http\Controllers\Admin\helpdesk\SettingsController();
        if ($ticket_number) {
            $number = $controller->nthTicketNumber($ticket_number, $type, $format, $force);
        } else {
            $number = $controller->switchNumber($format, $type);
        }
        $number = $this->generateTicketIfExist($number, $type, $format);
        return $number;
    }

    public function generateTicketIfExist($number, $type, $format)
    {
        $tickets = new Tickets();
        $ticket = $tickets->where('ticket_number', $number)->first();
        if ($ticket) {
            $number = $this->getNumber($number, $type, $format, false);
        }
        return $number;
    }

    /**
     * check email for dublicate entry.
     *
     * @param type $email
     *
     * @return type bool
     */
    public function checkEmail($email)
    {
        $check = User::where('email', '=', $email)->orWhere('user_name', $email)->orWhere('mobile', $email)->first();
        if ($check == true) {
            return $check;
        }
        return false;
    }

    /**
     * @category fucntion to check if mobile number is unqique or not
     *
     * @param string $mobile
     *
     * @return bool true(if mobile exists in users table)/false (if mobile does not exist in user table)
     */
    public function checkMobile($mobile)
    {
        $check = User::where('mobile', '=', $mobile)->first();
        if (count($check) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Create User while creating ticket.
     *
     * @param type $emailadd
     * @param type $username
     * @param type $subject
     * @param type $phone
     * @param type $helptopic
     * @param type $sla
     * @param type $priority
     * @param type $system
     *
     * @return type bool
     */
    public function create_user($emailadd, $username, $subject, $body, $phone, $phonecode, $mobile_number, $helptopic, $sla, $priority, $source, $headers, $dept, $assignto, $from_data, $auto_response, $status, $attach = [], $inline = [], $email_content = [])
    {
        // define global variables
        $email;
        $username;
        $unique = $emailadd;
        if (!$emailadd) {
            $unique = $mobile_number;
        }
        // check emails
        $ticket_creator = $username;
        $checkemail = $this->checkEmail($unique);
        $company = $this->company();
        if ($checkemail == false) {
            if ($mobile_number != '' || $mobile_number != null) {
                $check_mobile = $this->checkMobile($mobile_number);
                if ($check_mobile == true) {
                    return ['0' => 'not available', '1' => false];
                }
            }
            // Generate password
            $password = $this->generateRandomString();
            // create user
            $user = new User();
            $user_name_123 = explode('%$%', $username);
            $user_first_name = $user_name_123[0];
            if (isset($user_name_123[1])) {
                $user_last_name = $user_name_123[1];
                $user->last_name = $user_last_name;
            }
            $user->first_name = $user_first_name;
            $user_status = $this->checkUserVerificationStatus();
            $user->user_name = $unique;
            if ($emailadd == '') {
                $user->email = null;
            } else {
                $user->email = $emailadd;
            }
            $user->password = Hash::make($password);
            $user->phone_number = $phone;
            $user->country_code = $phonecode;
            if ($mobile_number == '') {
                $user->mobile = null;
            } else {
                $user->mobile = $mobile_number;
            }
            $user->role = 'user';
            $user->active = $user_status;
            $token = str_random(60);
            $user->remember_token = $token;
            // mail user his/her password
            \Event::fire(new \App\Events\ClientTicketFormPost($from_data, $emailadd, $source));
            if ($user->save()) {
                $user_id = $user->id;
                $email_mandatory = CommonSettings::select('status')->where('option_name', '=', 'email_mandatory')->first();
                if ($user_status == 0 || ($email_mandatory->status == 0 || $email_mandatory->status == '0')) {
                    $value = [
                        'full_name' => $username,
                        'email' => $emailadd,
                        'code' => $phonecode,
                        'mobile' => $mobile_number,
                        'user_name' => $unique,
                        'password' => $password,
                    ];
                    \Event::fire(new \App\Events\LoginEvent($value));
                }
                // Event fire
                \Event::fire(new \App\Events\ReadMailEvent($user_id, $password));
                $notification[] = [
                    'registration_notification_alert' => [
                        'userid' => $user_id,
                        'from' => $this->PhpMailController->mailfrom('1', '0'),
                        'message' => ['subject' => null, 'scenario' => 'registration-notification'],
                        'variable' => ['user' => $user->first_name, 'email_address' => $emailadd, 'user_password' => $password],
                    ],
                    'registration_alert' => [
                        'userid' => $user_id,
                        'from' => $this->PhpMailController->mailfrom('1', '0'),
                        'message' => ['subject' => null, 'scenario' => 'registration'],
                        'variable' => ['user' => $user->first_name, 'email_address' => $emailadd, 'password_reset_link' => faveoUrl('account/activate/' . $token)],
                    ],
                    'new_user_alert' => [
                        'userid' => $user_id,
                        'model' => $user,
                        'from' => $this->PhpMailController->mailfrom('1', '0'),
                        'message' => ['subject' => null, 'scenario' => 'new-user'],
                        'variable' => ['user' => $user->first_name, 'email_address' => $unique, 'user_profile_link' => faveoUrl('user/' . $user_id)],
                    ],
                ];
            }
        } else {
            $username = $checkemail->first_name;
            $user_id = $checkemail->id;
        }
        $ticket_number = $this->check_ticket($user_id, $subject, $body, $helptopic, $sla, $priority, $source, $headers, $dept, $assignto, $from_data, $status, $attach, $inline, $email_content);
        $ticket_number2 = $ticket_number[0];
        $ticketdata = Tickets::where('ticket_number', '=', $ticket_number2)->first();
        if ($ticketdata->assigned_to) {
            $notification[] = [
                'ticket_assign_alert' => [
                    'ticketid' => $ticketdata->id,
                    'from' => $this->PhpMailController->mailfrom('1', $ticketdata->dept_id),
                    'message' => ['subject' => 'Assign ticket ' . '[#' . $ticketdata->ticket_number . ']', 'scenario' => 'assign-ticket'],
                    'variable' => [
                        'ticket_number' => $ticketdata->ticket_number,
                        'ticket_assigner' => Auth::user()->first_name . ' ' . Auth::user()->last_name,
                        'ticket_link' => faveoUrl('thread/' . $ticketdata->id),
                    ],
                    'model' => $ticketdata,
                ],
            ];
        }
        if ($ticketdata->team_to) {
            $notification[] = [
                'ticket_assign_alert' => [
                    'ticketid' => $ticketdata->id,
                    'from' => $this->PhpMailController->mailfrom('1', $ticketdata->dept_id),
                    'message' => ['subject' => 'Assign ticket ' . '[#' . $ticketdata->ticket_number . ']', 'scenario' => 'team_assign_ticket'],
                    'variable' => [
                        'ticket_number' => $ticketdata->ticket_number,
                        'ticket_assigner' => Auth::user()->first_name . ' ' . Auth::user()->last_name,
                        'ticket_link' => faveoUrl('thread/' . $ticketdata->id),
                    ],
                    'model' => $ticketdata,
                ],
            ];
        }
        $threaddata = Ticket_Thread::where('ticket_id', '=', $ticketdata->id)->first();
        $is_reply = $ticket_number[1];
        //dd($source);
        $system = $this->system();
        $updated_subject = $threaddata->title . '[#' . $ticket_number2 . ']';
        if ($ticket_number2) {
            if ($is_reply == 0) {
                $mail = 'create-ticket-agent';
                $message = $threaddata->purify(false);
                if (Auth::user()) {
                    $sign = Auth::user()->agent_sign;
                } else {
                    $sign = $company;
                }
            } elseif ($is_reply == 1) {
                $this_thread = Ticket_Thread::where('ticket_id', '=', $ticketdata->id)->where('is_internal', 0)->orderBy('id', 'DESC')->first();
                $mail = 'ticket-reply-agent';
                $message = $this_thread->purify(false);
            }
            $notification[] = ['new_ticket_alert' => [
                'from' => $this->PhpMailController->mailfrom('0', $ticketdata->dept_id),
                'message' => [
                    'subject' => $updated_subject,
                    'body' => $message,
                    'scenario' => $mail,
                ],
                'variable' => [
                    //'ticket_agent_name' => $email_data['to_user_name'],
                    'ticket_client_name' => $username,
                    'ticket_client_email' => $emailadd,
                    //'user' => $email_data['to_user_name'],
                    'ticket_number' => $ticket_number2,
                    'email_address' => $emailadd,
                    'name' => $ticket_creator,
                    'system_link' => url('thread/' . $ticketdata->id),
                    //'agent_sign' => Auth::user()->agent_sign,
                ],
                'ticketid' => $ticketdata->id,
                'model' => $ticketdata,
                'userid' => $ticketdata->user_id,
                'thread' => $threaddata,
            ],
                'new_ticket_confirmation_alert' => [
                    'from' => $this->PhpMailController->mailfrom('0', $ticketdata->dept_id),
                    'message' => [
                        'subject' => $updated_subject,
                        'body' => $threaddata->purify(false),
                        'scenario' => 'create-ticket',
                    ],
                    'variable' => [
                        //'ticket_agent_name' => $email_data['to_user_name'],
                        'ticket_client_name' => $username,
                        'ticket_client_email' => $emailadd,
                        //'user' => $email_data['to_user_name'],
                        'ticket_number' => $ticket_number2,
                        'email_address' => $emailadd,
                        'name' => $ticket_creator,
                        'system_link' => url('thread/' . $ticketdata->id),
                        //'agent_sign' => Auth::user()->agent_sign,
                    ],
                    'ticketid' => $ticketdata->id,
                    'model' => $ticketdata,
                    'userid' => $ticketdata->user_id,
                ],
            ];
            $data = [
                'ticket_number' => $ticket_number2,
                'user_id' => $user_id,
                'subject' => $subject,
                'body' => $body,
                'status' => $status,
                'Priority' => $priority,
            ];
            \Event::fire('Create-Ticket', [$data]);
            $data = [
                'id' => $ticketdata->id,
            ];
            \Event::fire('ticket-assignment', [$data]);
            $alert = new Notifications\NotificationController();
            $alert->setDetails($notification);
            return ['0' => $ticket_number2, '1' => true];
        }
    }

    /**
     * Default helptopic.
     *
     * @return type string
     */
    public function default_helptopic()
    {
        $helptopic = '1';
        return $helptopic;
    }

    /**
     * Default SLA plan.
     *
     * @return type string
     */
    public function default_sla()
    {
        $sla = '1';
        return $sla;
    }

    /**
     * Default Priority.
     *
     * @return type string
     */
    public function default_priority()
    {
        $priority = '1';
        return $prioirty;
    }

    public function checkTicketForEmailReply($subject, $email_content)
    {
        $ticket = null;
        $email_thread = "";
        $read_subject = explode('[#', $subject);
        if (isset($read_subject[1])) {
            $separate = explode(']', $read_subject[1]);
            $number = substr($separate[0], 0, 20);
            $ticket = Tickets::where('ticket_number', '=', $number)->first();
        } elseif (count($email_content) > 0) {
            $reference_id = checkArray('reference_id', $email_content);
            //$msg_id = checkArray('message_id', $email_content);
            if ($reference_id) {
                $email_thread = \App\Model\helpdesk\Ticket\EmailThread::where('message_id', $reference_id)->select('id', 'ticket_id')->first();
            }
            if ($email_thread) {
                $ticket = $email_thread->ticket()->first();
            }
        }
        return $ticket;
    }

    /**
     * Check the response of the ticket.
     *
     * @param type $user_id
     * @param type $subject
     * @param type $body
     * @param type $helptopic
     * @param type $sla
     * @param type $priority
     *
     * @return type string
     */
    public function check_ticket($user_id, $subject, $body, $helptopic, $sla, $priority, $source, $headers, $dept, $assignto, $form_data, $status, $attach = [], $inline = [], $email_content = [])
    {
        $ticket = $this->checkTicketForEmailReply($subject, $email_content);
        $thread_body = explode('---Reply above this line---', $body);
        $body = $thread_body[0];
        if ($ticket) {
            $id = $ticket->id;
            $ticket_number = $ticket->ticket_number;
            if ($ticket->status > 1) {
                $ticket->status = 1;
                $ticket->closed = 0;
                $ticket->closed_at = date('Y-m-d H:i:s');
                $ticket->reopened = 1;
                $ticket->reopened_at = date('Y-m-d H:i:s');
                $ticket->save();
                $ticket_status = Ticket_Status::where('id', '=', 1)->first();
                $user_name = User::where('id', '=', $user_id)->first();
                if ($user_name->role == 'user') {
                    $username = $user_name->user_name;
                } elseif ($user_name->role == 'agent' or $user_name->role == 'admin') {
                    $username = $user_name->first_name . ' ' . $user_name->last_name;
                }
                // $ticket_threads = new Ticket_Thread();
                // $ticket_threads->ticket_id = $id;
                // $ticket_threads->user_id = $user_id;
                // $ticket_threads->is_internal = 1;
                // $ticket_threads->body = $ticket_status->message . ' ' . $username;
                // $ticket_threads->save();
                // event fire for internal notes
                //event to change status
                $data = [
                    'id' => $ticket_number,
                    'status' => 'Open',
                    'first_name' => $username,
                    'last_name' => '',
                ];
                \Event::fire('change-status', array($data));
            }
            if (isset($id)) {
                if ($this->ticketThread($subject, $body, $id, $user_id, $attach, $inline, $email_content)) {
                    //                        event fire for reply [$subject, $body, $id, $user_id]
                    return [$ticket_number, 1];
                }
            }
        } else {
            $ticket_number = $this->createTicket($user_id, $subject, $body, $helptopic, $sla, $priority, $source, $headers, $dept, $assignto, $form_data, $status, $attach, $inline, $email_content);
            return [$ticket_number, 0];
        }
    }

    /**
     * Create Ticket.
     *
     * @param type $user_id
     * @param type $subject
     * @param type $body
     * @param type $helptopic
     * @param type $sla
     * @param type $priority
     *
     * @return type string
     */
    public function createTicket($user_id, $subject, $body, $helptopic, $sla, $priority, $source, $headers, $dept, $assignto, $form_data, $status, $attach = [], $inline = [])
    {
        $ticket_number = '';
        $max_number = Tickets::whereRaw('id = (select max(`id`) from tickets)')->first();
        if ($max_number) {
            $ticket_number = $max_number->ticket_number;
        }
        if (!$sla) {
            $sla_plan = Sla_plan::where('status', 1)->first();
            $sla = $sla_plan->id;
        }
        $user_status = User::select('active')->where('id', '=', $user_id)->first();
        // dd($user_status->active);
        $ticket = new Tickets();
        $ticket->ticket_number = $this->ticketNumber($ticket_number);
        $ticket->user_id = $user_id;
        $ticket->dept_id = $dept;
        $ticket->help_topic_id = $helptopic;
        $ticket->sla = $sla;
        $ticket->assigned_to = $assignto;
        $ticket->priority_id = $priority;
        $ticket->source = $source;
        $ticket_status = $this->checkUserVerificationStatus();
        $ticket->status = $this->getStatus($user_id, $status);
        $ticket->save();
        $sla_plan = Sla_plan::where('id', '=', $sla)->first();
        $ovdate = $ticket->created_at;
        $new_date = date_add($ovdate, date_interval_create_from_date_string($sla_plan->grace_period));
        $ticket_number = $ticket->ticket_number;
        $id = $ticket->id;
        if (is_array($form_data) && count($form_data) > 0) {
            foreach ($form_data as $key => $data) {
                if (is_array($data)) {
                    foreach ($data as $title => $content) {
                        Ticket_Form_Data::create([
                            'ticket_id' => $id,
                            'title' => $title,
                            'key' => $key,
                            'content' => $content,
                        ]);
                    }
                }
            }
        }
        // store collaborators
        $this->storeCollaborators($headers, $id);
        if ($this->ticketThread($subject, $body, $id, $user_id, $attach, $inline, $inline) == true) {
            $ticket->duedate = $new_date;
            $ticket->save();
            return $ticket_number;
        }
    }

    public function getStatus($requester_id, $status = "")
    {
        if (!$status) {
            $requester = User::where('id', $requester_id)->first();
            $statuses = new Ticket_Status();
            $name = 'open';
            if ($requester->isDeleted() || $requester->isBan()) {
                $name = 'spam';
            }
            $ticket_status = $statuses->where('state', $name)->first();
            if (!$ticket_status) {
                $ticket_status = $statuses->first();
            }
            $status = $ticket_status->id;
        }
        return $status;
    }

    /**
     * Generate Ticket Thread.
     *
     * @param type $subject
     * @param type $body
     * @param type $id
     * @param type $user_id
     *
     * @return type
     */
    public function ticketThread($subject, $body, $id, $user_id, $attach = [], $inline = [], $email_content = [])
    {
        $thread = new Ticket_Thread();
        $thread->user_id = $user_id;
        $thread->ticket_id = $id;
        $thread->poster = 'client';
        $thread->title = $subject;
        $thread->body = $body;
        if ($thread->save()) {
            $this->saveEmailThread($thread, $email_content);
            $this->saveReplyAttachment($thread, $attach, $inline);
            \Event::fire('ticket.details', ['ticket' => $thread]); //get the ticket details
            return true;
        }
    }

    public function saveEmailThread($thread, $content)
    {
        $ticket_id = $thread->ticket_id;
        if (is_array($content) && count($content) > 0) {
            $thread->emailThread()->create([
                'ticket_id' => $ticket_id,
                'message_id' => checkArray('message_id', $content),
                'uid' => checkArray('uid', $content),
                'reference_id' => checkArray('reference_id', $content),
            ]);
        }
    }

    public function saveReplyAttachment($thread, $attachments, $inlines)
    {
        $drive = storageDrive();
        $thread_id = $thread->id;
        $attach = $thread->attach();
        if ($attachments && count($attachments) > 0) {
            foreach ($attachments as $attachment) {
                if (is_object($attachment)) {
                    $storage = new \App\FaveoStorage\Controllers\StorageController();
                    $thread = $storage->saveObjectAttachments($thread_id, $attachment);
                }
                if (is_array($attachment)) {
                    $attach->create([
                        'thread_id' => $thread_id,
                        'name' => $attachment['filename'],
                        'size' => $attachment['size'],
                        'type' => $attachment['type'],
                        'poster' => 'ATTACHMENT',
                        'path' => $attachment['path'],
                        'driver' => $drive,
                    ]);
                }
            }
        }
        if ($inlines && count($inlines) > 0) {
            foreach ($inlines as $inline) {
                $attach->create([
                    'thread_id' => $thread_id,
                    'name' => $inline['filename'],
                    'size' => $inline['size'],
                    'type' => $inline['type'],
                    'poster' => 'INLINE',
                    'path' => $inline['path'],
                    'driver' => $drive,
                ]);
            }
        }
        return $thread;
    }

    /**
     * Generate a random string for password.
     *
     * @param type $length
     *
     * @return type string
     */
    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * function to Ticket Close.
     *
     * @param type $id
     * @param type Tickets $ticket
     *
     * @return type string
     */
    public function close($id, Tickets $ticket, $api = true)
    {
        if (!$this->ticket_policy->close()) {
            if ($api) {
                return response()->json(['message' => 'permission denied'], 403);
            }
            return redirect('dashboard')->with('fails', 'Permission denied');
        }
        $ticket = Tickets::where('id', '=', $id)->first();
        if (Auth::user()->role == 'user') {
            $ticket_status = $ticket->where('id', '=', $id)->where('user_id', '=', Auth::user()->id)->first();
        } else {
            $ticket_status = $ticket->where('id', '=', $id)->first();
        }
        // checking for unautherised access attempt on other than owner ticket id
        if ($ticket_status == null) {
            return redirect()->route('unauth');
        }
        $ticket_status->status = 3;
        $ticket_status->closed = 1;
        $ticket_status->closed_at = date('Y-m-d H:i:s');
        $ticket_status->save();
        $ticket_thread = Ticket_Thread::where('ticket_id', '=', $ticket_status->id)->first();
        $ticket_subject = $ticket_thread->title;
        $ticket_status_message = Ticket_Status::where('id', '=', $ticket_status->status)->first();
        $thread = new Ticket_Thread();
        $thread->ticket_id = $ticket_status->id;
        $thread->user_id = Auth::user()->id;
        $thread->is_internal = 1;
        $thread->body = $ticket_status_message->message . ' ' . Auth::user()->first_name . ' ' . Auth::user()->last_name;
        $thread->save();
        $user_id = $ticket_status->user_id;
        $user = User::where('id', '=', $user_id)->first();
        $email = $user->email;
        $user_name = $user->user_name;
        $ticket_number = $ticket_status->ticket_number;
        $system_from = $this->company();
        $sending_emails = Emails::where('department', '=', $ticket_status->dept_id)->first();
        if ($sending_emails == null) {
            $from_email = $this->system_mail();
        } else {
            $from_email = $sending_emails->id;
        }
        try {
            $this->PhpMailController->sendmail($from = $this->PhpMailController->mailfrom('0', $ticket_status->dept_id), $to = ['name' => $user_name, 'email' => $email], $message = ['subject' => $ticket_subject . '[#' . $ticket_number . ']', 'scenario' => 'close-ticket'], $template_variables = ['ticket_number' => $ticket_number]);
        } catch (\Exception $e) {
            return 0;
        }
        $data = [
            'id' => $ticket_status->ticket_number,
            'status' => 'Closed',
            'first_name' => Auth::user()->first_name,
            'last_name' => Auth::user()->last_name,
        ];
        \Event::fire('change-status', [$data]);
        return 'your ticket' . $ticket_status->ticket_number . ' has been closed';
    }

    /**
     * function to Ticket resolved.
     *
     * @param type $id
     * @param type Tickets $ticket
     *
     * @return type string
     */
    public function resolve($id, Tickets $ticket, $api = true)
    {
        if (!$this->ticket_policy->close()) {
            if ($api) {
                return response()->json(['message' => 'permission denied'], 403);
            }
            return redirect('dashboard')->with('fails', 'Permission denied');
        }
        if (Auth::user()->role == 'user') {
            $ticket_status = $ticket->where('id', '=', $id)->where('user_id', '=', Auth::user()->id)->first();
        } else {
            $ticket_status = $ticket->where('id', '=', $id)->first();
        }
        // checking for unautherised access attempt on other than owner ticket id
        if ($ticket_status == null) {
            return redirect()->route('unauth');
        }
        //        $ticket_status = $ticket->where('id', '=', $id)->first();
        $ticket_status->status = 2;
        $ticket_status->closed = 1;
        $ticket_status->closed_at = date('Y-m-d H:i:s');
        $ticket_status->save();
        $ticket_status_message = Ticket_Status::where('id', '=', $ticket_status->status)->first();
        $thread = new Ticket_Thread();
        $thread->ticket_id = $ticket_status->id;
        $thread->user_id = Auth::user()->id;
        $thread->is_internal = 1;
        if (Auth::user()->first_name != null) {
            $thread->body = $ticket_status_message->message . ' ' . Auth::user()->first_name . ' ' . Auth::user()->last_name;
        } else {
            $thread->body = $ticket_status_message->message . ' ' . Auth::user()->user_name;
        }
        $thread->save();
        $data = [
            'id' => $ticket_status->ticket_number,
            'status' => 'Resolved',
            'first_name' => Auth::user()->first_name,
            'last_name' => Auth::user()->last_name,
        ];
        \Event::fire('change-status', [$data]);
        return 'your ticket' . $ticket_status->ticket_number . ' has been resolved';
    }

    /**
     * function to Open Ticket.
     *
     * @param type $id
     * @param type Tickets $ticket
     *
     * @return type
     */
    public function open($id, Tickets $ticket)
    {
        if (Auth::user()->role == 'user') {
            $ticket_status = $ticket->where('id', '=', $id)->where('user_id', '=', Auth::user()->id)->first();
        } else {
            $ticket_status = $ticket->where('id', '=', $id)->first();
        }
        // checking for unautherised access attempt on other than owner ticket id
        if ($ticket_status == null) {
            return redirect()->route('unauth');
        }
        $ticket_status->status = 1;
        $ticket_status->reopened_at = date('Y-m-d H:i:s');
        $ticket_status->save();
        $ticket_status_message = Ticket_Status::where('id', '=', $ticket_status->status)->first();
        $thread = new Ticket_Thread();
        $thread->ticket_id = $ticket_status->id;
        $thread->user_id = Auth::user()->id;
        $thread->is_internal = 1;
        $thread->body = $ticket_status_message->message . ' ' . Auth::user()->first_name . ' ' . Auth::user()->last_name;
        $thread->save();
        $data = [
            'id' => $ticket_status->ticket_number,
            'status' => 'Open',
            'first_name' => Auth::user()->first_name,
            'last_name' => Auth::user()->last_name,
        ];
        \Event::fire('change-status', [$data]);
        return 'your ticket' . $ticket_status->ticket_number . ' has been opened';
    }

    /**
     * Function to delete ticket.
     *
     * @param type $id
     * @param type Tickets $ticket
     *
     * @return type string
     */
    public function delete($id, Tickets $ticket, $api = true)
    {
        if (!$this->ticket_policy->delete()) {
            if ($api) {
                return response()->json(['message' => 'permission denied'], 403);
            }
            return redirect('dashboard')->with('fails', 'Permission denied');
        }
        $ticket_delete = $ticket->where('id', '=', $id)->first();
        if ($ticket_delete->status == 5) {
            $ticket_delete->delete();
            $ticket_threads = Ticket_Thread::where('ticket_id', '=', $id)->get();
            foreach ($ticket_threads as $ticket_thread) {
                $ticket_thread->delete();
            }
            $ticket_attachments = Ticket_attachments::where('ticket_id', '=', $id)->get();
            foreach ($ticket_attachments as $ticket_attachment) {
                $ticket_attachment->delete();
            }
            $data = [
                'id' => $ticket_delete->ticket_number,
                'status' => 'Deleted',
                'first_name' => Auth::user()->first_name,
                'last_name' => Auth::user()->last_name,
            ];
            \Event::fire('change-status', [$data]);
            return 'your ticket has been delete';
        } else {
            $ticket_delete->is_deleted = 1;
            $ticket_delete->status = 5;
            $ticket_delete->save();
            $ticket_status_message = Ticket_Status::where('id', '=', $ticket_delete->status)->first();
            $thread = new Ticket_Thread();
            $thread->ticket_id = $ticket_delete->id;
            $thread->user_id = Auth::user()->id;
            $thread->is_internal = 1;
            $thread->body = $ticket_status_message->message . ' ' . Auth::user()->first_name . ' ' . Auth::user()->last_name;
            $thread->save();
            $data = [
                'id' => $ticket_delete->ticket_number,
                'status' => 'Deleted',
                'first_name' => Auth::user()->first_name,
                'last_name' => Auth::user()->last_name,
            ];
            \Event::fire('change-status', [$data]);
            return 'your ticket' . $ticket_delete->ticket_number . ' has been delete';
        }
    }

    /**
     * Function to ban an email.
     *
     * @param type $id
     * @param type Tickets $ticket
     *
     * @return type string
     */
    public function ban($id, Tickets $ticket, $api = true)
    {
        if (!$this->ticket_policy->ban()) {
            if ($api) {
                return response()->json(['message' => 'permission denied'], 403);
            }
            return redirect('dashboard')->with('fails', 'Permission denied');
        }
        $ticket_ban = $ticket->where('id', '=', $id)->first();
        $ban_email = $ticket_ban->user_id;
        $user = User::where('id', '=', $ban_email)->first();
        $user->ban = 1;
        $user->save();
        $Email = $user->email;
        return 'the user has been banned';
    }

    /**
     * function to assign ticket.
     *
     * @param type $id
     *
     * @return type bool
     */
    public function assign($id, $api = true)
    {
        if (!$this->ticket_policy->assign()) {
            if ($api) {
                return response()->json(['message' => 'permission denied'], 403);
            }
            return redirect('dashboard')->with('fails', 'Permission denied');
        }
        $ticket_array = [];
        if (strpos($id, ',') !== false) {
            $ticket_array = explode(',', $id);
        } else {
            array_push($ticket_array, $id);
        }
        $UserEmail = Input::get('assign_to');
        $assign_to = explode('_', $UserEmail);
        $user_detail = null;
        foreach ($ticket_array as $id) {
            $ticket = Tickets::where('id', '=', $id)->first();
            if ($assign_to[0] == 'team') {
                $ticket->team_id = $assign_to[1];
                $team_detail = Teams::where('id', '=', $assign_to[1])->first();
                $assignee = $team_detail->name;
                $ticket_number = $ticket->ticket_number;
                $ticket->save();
                $ticket_thread = Ticket_Thread::where('ticket_id', '=', $id)->first();
                $ticket_subject = $ticket_thread->title;
                $thread = new Ticket_Thread();
                $thread->ticket_id = $ticket->id;
                $thread->user_id = Auth::user()->id;
                $thread->is_internal = 1;
                $thread->body = 'This Ticket has been assigned to ' . $assignee;
                $thread->save();
            } elseif ($assign_to[0] == 'user') {
                $ticket->assigned_to = $assign_to[1];
                if ($user_detail === null) {
                    $user_detail = User::where('id', '=', $assign_to[1])->first();
                    $assignee = $user_detail->first_name . ' ' . $user_detail->last_name;
                }
                $company = $this->company();
                $system = $this->system();
                $ticket_number = $ticket->ticket_number;
                $ticket->save();
                $data = [
                    'id' => $id,
                ];
                \Event::fire('ticket-assignment', [$data]);
                $ticket_thread = Ticket_Thread::where('ticket_id', '=', $id)->first();
                $ticket_subject = $ticket_thread->title;
                $thread = new Ticket_Thread();
                $thread->ticket_id = $ticket->id;
                $thread->user_id = Auth::user()->id;
                $thread->is_internal = 1;
                $thread->body = 'This Ticket has been assigned to ' . $assignee;
                $thread->save();
                $agent = $user_detail->first_name;
                $agent_email = $user_detail->email;
                $ticket_link = route('ticket.thread', $id);
                $master = Auth::user()->first_name . ' ' . Auth::user()->last_name;
                try {
                    $this->PhpMailController->sendmail($from = $this->PhpMailController->mailfrom('0', $ticket->dept_id), $to = ['name' => $agent, 'email' => $agent_email], $message = ['subject' => $ticket_subject . '[#' . $ticket_number . ']', 'scenario' => 'assign-ticket'], $template_variables = ['ticket_agent_name' => $agent, 'ticket_number' => $ticket_number, 'ticket_assigner' => $master, 'ticket_link' => $ticket_link]);
                } catch (\Exception $e) {
                    return 0;
                }
            }
        }
        return 1;
    }

    /**
     * Function to post internal note.
     *
     * @param type $id
     *
     * @return type bool
     */
    public function InternalNote($id)
    {
        //dd($id);
        $InternalContent = Input::get('InternalContent');
        //$thread = Ticket_Thread::where('ticket_id', '=', $id)->first();
        $NewThread = new Ticket_Thread();
        $NewThread->ticket_id = $id;
        $NewThread->user_id = Auth::user()->id;
        $NewThread->is_internal = 1;
        $NewThread->thread_type = 'note';
        $NewThread->poster = Auth::user()->role;
        //$NewThread->title = $thread->title;
        $NewThread->body = $InternalContent;
        $NewThread->save();
        $data = [
            'ticket_id' => $id,
            'u_id' => Auth::user()->first_name . ' ' . Auth::user()->last_name,
            'body' => $InternalContent,
        ];
        \Event::fire('Reply-Ticket', [$data]);
        return 1;
    }

    /**
     * Function to surrender a ticket.
     *
     * @param type $id
     *
     * @return type bool
     */
    public function surrender($id)
    {
        $ticket = Tickets::where('id', '=', $id)->first();
        $InternalContent = Auth::user()->first_name . ' ' . Auth::user()->last_name . ' has Surrendered the assigned Ticket';
        $thread = Ticket_Thread::where('ticket_id', '=', $id)->first();
        $NewThread = new Ticket_Thread();
        $NewThread->ticket_id = $thread->ticket_id;
        $NewThread->user_id = Auth::user()->id;
        $NewThread->is_internal = 1;
        $NewThread->poster = Auth::user()->role;
        $NewThread->title = $thread->title;
        $NewThread->body = $InternalContent;
        $NewThread->save();
        $ticket->assigned_to = null;
        $ticket->save();
        return 1;
    }

    /**
     * Search.
     *
     * @param type $keyword
     *
     * @return type array
     */
    public function search($keyword)
    {
        if (isset($keyword)) {
            $data = ['ticket_number' => Tickets::search($keyword)];
            return $data;
        } else {
            return 'no results';
        }
    }

    /**
     * Search.
     *
     * @param type $keyword
     *
     * @return type array
     */
    public function stores($ticket_number)
    {
        $this->layout->header = $ticket_number;
        $content = View::make('themes.default1.admin.tickets.ticketsearch', with(new Tickets()))
            ->with('header', $this->layout->header)
            ->with('ticket_number', \App\Model\Tickets::stores($ticket_number));
        if (Request::header('X-PJAX')) {
            return $content;
        } else {
            $this->layout->content = $content;
        }
    }

    /**
     * store_collaborators.
     *
     * @param type $headers
     *
     * @return type
     */
    public function storeCollaborators($headers, $id)
    {
        $company = $this->company();
        if (isset($headers)) {
            foreach ($headers as $email => $name) {
                if ($name == null) {
                    $name = $email;
                }
                $name = $name;
                $email = $email;
                if ($this->checkEmail($email) == false) {
                    $create_user = new User();
                    $create_user->first_name = $name;
                    $create_user->user_name = $email;
                    $create_user->email = $email;
                    $create_user->active = 1;
                    $create_user->role = 'user';
                    $password = $this->generateRandomString();
                    $create_user->password = Hash::make($password);
                    $create_user->save();
                    $user_id = $create_user->id;
                    try {
                        $this->PhpMailController->sendmail($from = $this->PhpMailController->mailfrom('1', '0'), $to = ['name' => $name, 'email' => $email], $message = ['subject' => 'password', 'scenario' => 'registration-notification'], $template_variables = ['user' => $name, 'email_address' => $email, 'user_password' => $password]);
                    } catch (\Exception $e) {

                    }
                } else {
                    $user = $this->checkEmail($email);
                    $user_id = $user->id;
                }
                $collaborator_store = new Ticket_Collaborator();
                $collaborator_store->isactive = 1;
                $collaborator_store->ticket_id = $id;
                $collaborator_store->user_id = $user_id;
                $collaborator_store->role = 'ccc';
                $collaborator_store->save();
            }
        }
        return true;
    }

    /**
     * company.
     *
     * @return type
     */
    public function company()
    {
        $company = Company::Where('id', '=', '1')->first();
        if ($company->company_name == null) {
            $company = 'Support Center';
        } else {
            $company = $company->company_name;
        }
        return $company;
    }

    /**
     * system.
     *
     * @return type
     */
    public function system()
    {
        $system = System::Where('id', '=', '1')->first();
        if ($system->name == null) {
            $system = 'Support Center';
        } else {
            $system = $system->name;
        }
        return $system;
    }

    /**
     * shows trashed tickets.
     *
     * @return type response
     */
    public function trash()
    {
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.ticket.trash', compact('table'));
    }

    /**
     * shows unassigned tickets.
     *
     * @return type
     */
    public function unassigned()
    {
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.ticket.unassigned', compact('table'));
    }

    /**
     * shows tickets assigned to Auth::user().
     *
     * @return type
     */
    public function myticket()
    {
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.ticket.myticket', compact('table'));
    }

    /**
     * cleanMe.
     *
     * @param type $input
     *
     * @return type
     */
    public function cleanMe($input)
    {
        $input = mysqli_real_escape_string($input);
        $input = htmlspecialchars($input, ENT_IGNORE, 'utf-8');
        $input = strip_tags($input);
        $input = stripslashes($input);
        return $input;
    }

    /**
     * autosearch.
     *
     * @param type Image $image
     *
     * @return type json
     */
    public function autosearch($id)
    {
        $term = \Input::get('term');
        $user = \App\User::where('email', 'LIKE', '%' . $term . '%')->pluck('email');
        echo json_encode($user);
    }

    /**
     * autosearch2.
     *
     * @param type Image $image
     *
     * @return type json
     */
    public function autosearch2(User $user)
    {
        $user = $user->pluck('email');
        echo json_encode($user);
    }

    /**
     * autosearch.
     *
     * @param type Image $image
     *
     * @return type json
     */
    public function usersearch()
    {
        $email = Input::get('search');
        $ticket_id = Input::get('ticket_id');
        $data = User::where('email', '=', $email)->first();
        if ($data == null) {
            return '<div id="alert11" class="alert alert-warning alert-dismissable">'
            . '<button id="dismiss11" type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>'
            . '<i class="icon fa fa-ban"></i>'
            . 'This Email doesnot exist in the system'
            . '</div>'
            . '</div>';
        }
        $ticket_collaborator = Ticket_Collaborator::where('ticket_id', '=', $ticket_id)->where('user_id', '=', $data->id)->first();
        if (!isset($ticket_collaborator)) {
            $ticket_collaborator = new Ticket_Collaborator();
            $ticket_collaborator->isactive = 1;
            $ticket_collaborator->ticket_id = $ticket_id;
            $ticket_collaborator->user_id = $data->id;
            $ticket_collaborator->role = 'ccc';
            $ticket_collaborator->save();
            return '<div id="alert11" class="alert alert-dismissable" style="color:#60B23C;background-color:#F2F2F2;"><button id="dismiss11" type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><h4><i class="icon fa fa-check"></i>Success!</h4><h4><i class="icon fa fa-user"></i>' . $data->user_name . '</h4><div id="message-success1">' . $data->email . '</div></div>';
        } else {
            return '<div id="alert11" class="alert alert-warning alert-dismissable"><button id="dismiss11" type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><h4><i class="icon fa fa-warning"></i>' . $data->user_name . '</h4><div id="message-success1">' . $data->email . '<br/>This user already Collaborated</div></div>';
        }
    }

    /**
     * useradd.
     *
     * @param type Image $image
     *
     * @return type json
     */
    public function useradd()
    {
        $name = Input::get('name');
        $email = Input::get('email');
        $validator = \Validator::make(
            ['email' => $email, 'name' => $name], ['email' => 'required|email']
        );
        if ($validator->fails()) {
            return 'Invalid email address.';
        }
        $ticket_id = Input::get('ticket_id');
        $user_search = User::where('email', '=', $email)->first();
        if (isset($user_serach)) {
            return '<div id="alert11" class="alert alert-warning alert-dismissable" ><button id="dismiss11" type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><h4><i class="icon fa fa-alert"></i>Alert!</h4><div id="message-success1">This user already Exists</div></div>';
        } else {
            $company = $this->company();
            $user = new User();
            $user->first_name = $name;
            $user->user_name = $email;
            $user->email = $email;
            $password = $this->generateRandomString();
            $user->password = \Hash::make($password);
            $user->role = 'user';
            $user->active = 1;
            if ($user->save()) {
                $user_id = $user->id;
                $this->PhpMailController->sendmail($from = $this->PhpMailController->mailfrom('1', '0'), $to = ['name' => $name, 'email' => $email], $message = ['subject' => 'Password', 'scenario' => 'registration-notification'], $template_variables = ['user' => $name, 'email_address' => $email, 'user_password' => $password]);
            }
            $ticket_collaborator = new Ticket_Collaborator();
            $ticket_collaborator->isactive = 1;
            $ticket_collaborator->ticket_id = $ticket_id;
            $ticket_collaborator->user_id = $user->id;
            $ticket_collaborator->role = 'ccc';
            $ticket_collaborator->save();
            return '<div id="alert11" class="alert alert-dismissable" style="color:#60B23C;background-color:#F2F2F2;"><button id="dismiss11" type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><h4><i class="icon fa fa-user"></i>' . $user->user_name . '</h4><div id="message-success1">' . $user->email . '</div></div>';
        }
    }

    /**
     * user remove.
     *
     * @return type
     */
    public function userremove()
    {
        $id = Input::get('data1');
        $ticket_collaborator = Ticket_Collaborator::where('id', '=', $id)->delete();
        return 1;
    }

    /**
     * select_all.
     *
     * @return type
     */
    public function select_all()
    {
        if (Input::has('select_all')) {
            $selectall = Input::get('select_all');
            $value = Input::get('submit');
            foreach ($selectall as $delete) {
                $ticket = Tickets::whereId($delete)->first();
                if ($value == 'Delete') {
                    $this->delete($delete, new Tickets());
                } elseif ($value == 'Close') {
                    $this->close($delete, new Tickets());
                } elseif ($value == 'Open') {
                    $this->open($delete, new Tickets());
                } elseif ($value == 'Delete forever') {
                    $notification = Notification::select('id')->where('model_id', '=', $ticket->id)->get();
                    foreach ($notification as $id) {
                        $user_notification = UserNotification::where(
                            'notification_id', '=', $id->id);
                        $user_notification->delete();
                    }
                    $notification = Notification::select('id')->where('model_id', '=', $ticket->id);
                    $notification->delete();
                    $thread = Ticket_Thread::where('ticket_id', '=', $ticket->id)->get();
                    foreach ($thread as $th_id) {
                        // echo $th_id->id." ";
                        $attachment = Ticket_attachments::where('thread_id', '=', $th_id->id)->get();
                        if (count($attachment)) {
                            foreach ($attachment as $a_id) {
                                // echo $a_id->id . ' ';
                                $attachment = Ticket_attachments::find($a_id->id);
                                $attachment->delete();
                            }
                            // echo "<br>";
                        }
                        $thread = Ticket_Thread::find($th_id->id);
                        //                        dd($thread);
                        $thread->delete();
                    }
                    $collaborators = Ticket_Collaborator::where('ticket_id', '=', $ticket->id)->get();
                    if (count($collaborators)) {
                        foreach ($collaborators as $collab_id) {
                            // echo $collab_id->id;
                            $collab = Ticket_Collaborator::find($collab_id->id);
                            $collab->delete();
                        }
                    }
                    $tickets = Tickets::find($ticket->id);
                    $tickets->delete();
                    $data = ['id' => $ticket->id];
                    \Event::fire('ticket-permanent-delete', [$data]);
                }
            }
            if ($value == 'Delete') {
                return redirect()->back()->with('success', lang::get('lang.moved_to_trash'));
            } elseif ($value == 'Close') {
                return redirect()->back()->with('success', Lang::get('lang.tickets_have_been_closed'));
            } elseif ($value == 'Open') {
                return redirect()->back()->with('success', Lang::get('lang.tickets_have_been_opened'));
            } else {
                return redirect()->back()->with('success', Lang::get('lang.hard-delete-success-message'));
            }
        }
        return redirect()->back()->with('fails', 'None Selected!');
    }

    /**
     * user time zone.
     *
     * @param type $utc
     *
     * @return type date
     */
    public static function usertimezone($utc)
    {
        $set = System::whereId('1')->first();
        $timezone = Timezones::whereId($set->time_zone)->first();
        $tz = $timezone->name;
        $format = $set->date_time_format;
        date_default_timezone_set($tz);
        $offset = date('Z', strtotime($utc));
        $format = Date_time_format::whereId($format)->first()->format;
        $date = date($format, strtotime($utc) + $offset);
        return $date;
    }

    /**
     * adding offset to updated_at time.
     *
     * @return date
     */
    public static function timeOffset($utc)
    {
        $set = System::whereId('1')->first();
        $timezone = Timezones::whereId($set->time_zone)->first();
        $tz = $timezone->name;
        date_default_timezone_set($tz);
        $offset = date('Z', strtotime($utc));
        return $offset;
    }

    /**
     * to get user date time format.
     *
     * @return string
     */
    public static function getDateTimeFormat()
    {
        $set = System::select('date_time_format')->whereId('1')->first();
        return $set->date_time_format;
    }

    /**
     * lock.
     *
     * @param type $id
     *
     * @return type null
     */
    public function lock($id)
    {
        $ticket = Tickets::where('id', '=', $id)->first();
        $ticket->lock_by = Auth::user()->id;
        $ticket->lock_at = date('Y-m-d H:i:s');
        $ticket->save();
    }

    /**
     * Show the deptopen ticket list page.
     *
     * @return type response
     */
    public function deptopen($id)
    {
        $dept = Department::where('name', '=', $id)->first();
        if (Auth::user()->role == 'agent') {
            if (Auth::user()->primary_dpt == $dept->id) {
                return view('themes.default1.agent.helpdesk.dept-ticket.tickets', compact('id'));
            } else {
                return redirect()->back()->with('fails', 'Unauthorised!');
            }
        } else {
            return view('themes.default1.agent.helpdesk.dept-ticket.tickets', compact('id'));
        }
    }

    public function deptTicket($dept, $status)
    {
        if (\Auth::user()->role === 'agent') {
            $dept2 = Department::where('id', '=', \Auth::user()->primary_dpt)->first();
            if ($dept !== $dept2->name) {
                return redirect()->back()->with('fails', Lang::get('lang.unauthorized_access'));
            }
        }
        $table = \Datatable::table()
            ->addColumn(
                '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
            ->noScript();
        return view('themes.default1.agent.helpdesk.dept-ticket.tickets', compact('dept', 'status', 'table'));
    }

    /**
     * Show the deptclose ticket list page.
     *
     * @return type response
     */
    public function deptclose($id)
    {
        $dept = Department::where('name', '=', $id)->first();
        if (Auth::user()->role == 'agent') {
            if (Auth::user()->primary_dpt == $dept->id) {
                return view('themes.default1.agent.helpdesk.dept-ticket.closed', compact('id'));
            } else {
                return redirect()->back()->with('fails', 'Unauthorised!');
            }
        } else {
            return view('themes.default1.agent.helpdesk.dept-ticket.closed', compact('id'));
        }
    }

    /**
     * Show the deptinprogress ticket list page.
     *
     * @return type response
     */
    public function deptinprogress($id)
    {
        $dept = Department::where('name', '=', $id)->first();
        if (Auth::user()->role == 'agent') {
            if (Auth::user()->primary_dpt == $dept->id) {
                return view('themes.default1.agent.helpdesk.dept-ticket.inprogress', compact('id'));
            } else {
                return redirect()->back()->with('fails', 'Unauthorised!');
            }
        } else {
            return view('themes.default1.agent.helpdesk.dept-ticket.inprogress', compact('id'));
        }
    }

    /**
     * Store ratings of the user.
     *
     * @return type Redirect
     */
    public function rating($id, Request $request, \App\Model\helpdesk\Ratings\RatingRef $rating_ref)
    {
        foreach ($request->all() as $key => $value) {
            if ($key == '_token') {
                continue;
            }
            if (strpos($key, '_') !== false) {
                $ratName = str_replace('_', ' ', $key);
            } else {
                $ratName = $key;
            }
            $ratID = \App\Model\helpdesk\Ratings\Rating::where('name', '=', $ratName)->first();
            $ratingrefs = $rating_ref->where('rating_id', '=', $ratID->id)->where('ticket_id', '=', $id)->first();
            if ($ratingrefs !== null) {
                $ratingrefs->rating_id = $ratID->id;
                $ratingrefs->ticket_id = $id;
                $ratingrefs->thread_id = '0';
                $ratingrefs->rating_value = $value;
                $ratingrefs->save();
            } else {
                $rating_ref->rating_id = $ratID->id;
                $rating_ref->ticket_id = $id;
                $rating_ref->thread_id = '0';
                $rating_ref->rating_value = $value;
                $rating_ref->save();
            }
        }
        return redirect()->back()->with('Success', 'Thank you for your rating!');
    }

    /**
     * Store Client rating about reply of agent quality.
     *
     * @return type Redirect
     */
    public function ratingReply($id, Request $request, \App\Model\helpdesk\Ratings\RatingRef $rating_ref)
    {
        foreach ($request->all() as $key => $value) {
            if ($key == '_token') {
                continue;
            }
            $key1 = explode(',', $key);
            if (strpos($key1[0], '_') !== false) {
                $ratName = str_replace('_', ' ', $key1[0]);
            } else {
                $ratName = $key1[0];
            }
            $ratID = \App\Model\helpdesk\Ratings\Rating::where('name', '=', $ratName)->first();
            $ratingrefs = $rating_ref->where('rating_id', '=', $ratID->id)->where('thread_id', '=', $key1[1])->first();
            if ($ratingrefs !== null) {
                $ratingrefs->rating_id = $ratID->id;
                $ratingrefs->ticket_id = $id;
                $ratingrefs->thread_id = $key1[1];
                $ratingrefs->rating_value = $value;
                $ratingrefs->save();
            } else {
                $rating_ref->rating_id = $ratID->id;
                $rating_ref->ticket_id = $id;
                $rating_ref->thread_id = $key1[1];
                $rating_ref->rating_value = $value;
                $rating_ref->save();
            }
        }
        return redirect()->back()->with('Success', 'Thank you for your rating!');
    }

    /**
     * System default email.
     */
    public function system_mail()
    {
        $email = Email::where('id', '=', '1')->first();
        return $email->sys_email;
    }

    /**
     * checkLock($id)
     * function to check and lock ticket.
     *
     * @param int $id
     *
     * @return int
     */
    public function checkLock($id)
    {
        $ticket = DB::table('tickets')->select('id', 'lock_at', 'lock_by')->where('id', '=', $id)->first();
        $cad = DB::table('settings_ticket')->select('collision_avoid')->where('id', '=', 1)->first();
        $cad = $cad->collision_avoid; //collision avoid duration defined in system
        $to_time = strtotime($ticket->lock_at); //last locking time
        $from_time = time(); //user system's cureent time
        // difference in last locking time and user system's current time
        $diff = round(abs($to_time - $from_time) / 60, 2);
        if ($diff < $cad && Auth::user()->id != $ticket->lock_by) {
            $user_data = User::select('user_name', 'first_name', 'last_name')->where('id', '=', $ticket->lock_by)->first();
            if ($user_data->first_name != '') {
                $name = $user_data->first_name . ' ' . $user_data->last_name;
            } else {
                $name = $user_data->username;
            }
            return Lang::get('lang.locked-ticket') . " <a href='" . route('user.show', $ticket->lock_by) . "'>" . $name . '</a>&nbsp;' . $diff . '&nbsp' . Lang::get('lang.minutes-ago');  //ticket is locked
        } elseif ($diff < $cad && Auth::user()->id == $ticket->lock_by) {
            $ticket = Tickets::where('id', '=', $id)->first();
            $ticket->lock_at = date('Y-m-d H:i:s');
            $ticket->save();
            return 4;  //ticket is locked by same user who is requesting access
        } else {
            if (Auth::user()->id == $ticket->lock_by) {
                $ticket = Tickets::where('id', '=', $id)->first();
                $ticket->lock_at = date('Y-m-d H:i:s');
                $ticket->save();
                return 1; //ticket is available and lock ticket for the same user who locked ticket previously
            } else {
                $ticket = Tickets::where('id', '=', $id)->first();
                $ticket->lock_by = Auth::user()->id;
                $ticket->lock_at = date('Y-m-d H:i:s');
                $ticket->save(); //ticket is available and lock ticket for new user
                return 2;
            }
        }
    }

    /**
     * function to Change owner.
     *
     * @param type $id
     *
     * @return type bool
     */
    public function changeOwner($id)
    {
        $action = Input::get('action');
        $email = Input::get('email');
        $ticket_id = Input::get('ticket_id');
        $send_mail = Input::get('send-mail');
        if ($action === 'change-add-owner') {
            $name = Input::get('name');
            $returnValue = $this->changeOwnerAdd($email, $name, $ticket_id);
            if ($returnValue === 0) {
                return 4;
            } elseif ($returnValue === 2) {
                return 5;
            } else {
                //do nothing
            }
        }
        $user = User::where('email', '=', $email)->first();
        $count = count($user);
        if ($count === 1) {
            $user_id = $user->id;
            $ticket = Tickets::where('id', '=', $id)->first();
            if ($user_id === (int)$ticket->user_id) {
                return 400;
            }
            $ticket_number = $ticket->ticket_number;
            $ticket->user_id = $user_id;
            $ticket->save();
            $ticket_thread = Ticket_Thread::where('ticket_id', '=', $id)->first();
            $ticket_subject = $ticket_thread->title;
            $thread = new Ticket_Thread();
            $thread->ticket_id = $ticket->id;
            $thread->user_id = Auth::user()->id;
            $thread->is_internal = 1;
            $thread->body = 'This ticket now belongs to ' . $user->user_name;
            $thread->save();
            //mail functionality
            $company = $this->company();
            $system = $this->system();
            $agent = $user->first_name;
            $agent_email = $user->email;
            $master = Auth::user()->first_name . ' ' . Auth::user()->last_name;
            if (Alert::first()->internal_status == 1 || Alert::first()->internal_assigned_agent == 1) {
                // ticket assigned send mail
                Mail::send('emails.Ticket_assign', ['agent' => $agent, 'ticket_number' => $ticket_number, 'from' => $company, 'master' => $master, 'system' => $system], function ($message) use ($agent_email, $agent, $ticket_number, $ticket_subject) {
                    $message->to($agent_email, $agent)->subject($ticket_subject . '[#' . $ticket_number . ']');
                });
            }
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * useradd.
     *
     * @param type Image $image
     *
     * @return type json
     */
    public function changeOwnerAdd($email, $name, $ticket_id)
    {
        $name = $name;
        $email = $email;
        $ticket_id = $ticket_id;
        $validator = \Validator::make(
            ['email' => $email,
                'name' => $name,], ['email' => 'required|email',
            ]
        );
        $user = User::where('email', '=', $email)->first();
        $count = count($user);
        if ($count === 1) {
            return 0;
        } elseif ($validator->fails()) {
            return 2;
        } else {
            $company = $this->company();
            $user = new User();
            $user->user_name = $name;
            $user->email = $email;
            $password = $this->generateRandomString();
            $user->password = \Hash::make($password);
            $user->role = 'user';
            if ($user->save()) {
                $user_id = $user->id;
                try {
                    $this->PhpMailController->sendmail($from = $this->PhpMailController->mailfrom('1', '0'), $to = ['name' => $name, 'email' => $email], $message = ['subject' => 'Password', 'scenario' => 'registration-notification'], $template_variables = ['user' => $name, 'email_address' => $email, 'user_password' => $password]);
                } catch (\Exception $e) {

                }
            }
            return 1;
        }
    }

    public function getMergeTickets($id)
    {
        if ($id == 0) {
            $t_id = Input::get('data1');
            foreach ($t_id as $value) {
                $title = Ticket_Thread::select('title')->where('ticket_id', '=', $value)->first();
                echo "<option value='$value'>" . $title->title . '</option>';
            }
        } else {
            $ticket = Tickets::select('user_id')->where('id', '=', $id)->first();
            $ticket_data = Tickets::select('ticket_number', 'id')
                ->where('user_id', '=', $ticket->user_id)->where('id', '!=', $id)->where('status', '=', 1)->get();
            foreach ($ticket_data as $value) {
                $title = Ticket_Thread::select('title')->where('ticket_id', '=', $value->id)->first();
                echo "<option value='$value->id'>" . $title->title . '</option>';
            }
        }
    }

    public function checkMergeTickets($id)
    {
        if ($id == 0) {
            if (Input::get('data1') == null || count(Input::get('data1')) == 1) {
                return 0;
            } else {
                $t_id = Input::get('data1');
                $previousValue = null;
                $match = 1;
                foreach ($t_id as $value) {
                    $ticket = Tickets::select('user_id')->where('id', '=', $value)->first();
                    if ($previousValue == null || $previousValue == $ticket->user_id) {
                        $previousValue = $ticket->user_id;
                        $match = 1;
                    } else {
                        $match = 2;
                        break;
                    }
                }
                return $match;
            }
        } else {
            $ticket = Tickets::select('user_id')->where('id', '=', $id)->first();
            $ticket_data = Tickets::select('ticket_number', 'id')
                ->where('user_id', '=', $ticket->user_id)
                ->where('id', '!=', $id)
                ->where('status', '=', 1)->get();
            if (isset($ticket_data) && count($ticket_data) >= 1) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    public function mergeTickets($id)
    {
        // split the phrase by any number of commas or space characters,
        // which include " ", \r, \t, \n and \f
        $t_id = preg_split("/[\s,]+/", $id);
        if (count($t_id) > 1) {
            $p_id = Input::get('p_id'); //parent ticket id
            $t_id = array_diff($t_id, [$p_id]);
        } else {
            $t_id = Input::get('t_id'); //getting array of tickets to merge
            if ($t_id == null) {
                return 2;
            } else {
                $temp_id = Input::get('p_id'); //getting parent ticket
                if ($id == $temp_id) {
                    $p_id = $id;
                } else {
                    $p_id = $temp_id;
                    array_push($t_id, $id);
                    $t_id = array_diff($t_id, [$temp_id]);
                }
            }
        }
        $parent_ticket = Tickets::select('ticket_number')->where('id', '=', $p_id)->first();
        $parent_thread = Ticket_Thread::where('ticket_id', '=', $p_id)->first();
        foreach ($t_id as $value) {//to create new thread of the tickets to be merged with parent
            $thread = Ticket_Thread::where('ticket_id', '=', $value)->first();
            $ticket = Tickets::select('ticket_number')->where('id', '=', $value)->first();
            Ticket_Thread::where('ticket_id', '=', $value)
                ->update(['ticket_id' => $p_id]);
            Ticket_Form_Data::where('ticket_id', '=', $value)
                ->update(['ticket_id' => $p_id]);
            Ticket_Collaborator::where('ticket_id', '=', $value)
                ->update(['ticket_id' => $p_id]);
            Tickets::where('id', '=', $value)
                ->update(['status' => 3]);
            //event has $p_id and $value
            \Event::fire('ticket.merge', [['parent' => $p_id, 'child' => $value]]);
            if (!empty(Input::get('reason'))) {
                $reason = Input::get('reason');
            } else {
                $reason = Lang::get('lang.no-reason');
            }
            if (!empty(Input::get('title'))) {
                Ticket_Thread::where('ticket_id', '=', $p_id)->first()
                    ->update(['title' => Input::get('title')]);
            }
            $new_thread = new Ticket_Thread();
            $new_thread->ticket_id = $thread->ticket_id;
            $new_thread->user_id = Auth::user()->id;
            $new_thread->is_internal = 0;
            $new_thread->title = $thread->title;
            $new_thread->body = Lang::get('lang.get_merge_message') .
                "&nbsp;&nbsp;<a href='" . route('ticket.thread', [$p_id]) .
                "'>#" . $parent_ticket->ticket_number . '</a><br><br><b>' . Lang::get('lang.merge-reason') . ':</b>&nbsp;&nbsp;' . $reason;
            $new_thread->format = $thread->format;
            $new_thread->ip_address = $thread->ip_address;
            $new_parent_thread = new Ticket_Thread();
            $new_parent_thread->ticket_id = $p_id;
            $new_parent_thread->user_id = Auth::user()->id;
            $new_parent_thread->is_internal = 1;
            $new_parent_thread->title = $thread->title;
            $new_parent_thread->body = Lang::get('lang.ticket') . "&nbsp;<a href='" . route('ticket.thread', [$value]) . "'>#" . $ticket->ticket_number . '</a>&nbsp' . Lang::get('lang.ticket_merged') . '<br><br><b>' . Lang::get('lang.merge-reason') . ':</b>&nbsp;&nbsp;' . $reason;
            $new_parent_thread->format = $parent_thread->format;
            $new_parent_thread->ip_address = $parent_thread->ip_address;
            if ($new_thread->save() && $new_parent_thread->save()) {
                $success = 1;
            } else {
                $success = 0;
            }
        }
        $this->sendMergeNotification($p_id, $t_id);
        return $success;
    }

    public function getParentTickets($id)
    {
        $title = Ticket_Thread::select('title')->where('ticket_id', '=', $id)->first();
        echo "<option value='$id'>" . $title->title . '</option>';
        $tickets = Input::get('data1');
        foreach ($tickets as $value) {
            $title = Ticket_Thread::select('title')->where('ticket_id', '=', $value)->first();
            echo "<option value='$value'>" . $title->title . '</option>';
        }
    }

    /*
     * chumper's function to return data to chumper datatable.
     * @param Array-object $tickets
     *
     * @return Array-object
     */
    public static function getTable($tickets)
    {
        return \Datatables::of($tickets)
            ->addColumn('id', function ($tickets) {
                return "<input type='checkbox' name='select_all[]' id='" . $tickets->id . "' onclick='someFunction(this.id)' class='selectval icheckbox_flat-blue' value='" . $tickets->id . "'></input>";
            })
            ->addColumn('title', function ($tickets) {
                if (isset($tickets->ticket_title)) {
                    $string = str_limit($tickets->ticket_title, 20);
                } else {
                    $string = '(no subject)';
                }
                $collab = $tickets->countcollaborator;
                if ($collab > 0) {
                    $collabString = '&nbsp;<i class="fa fa-users"></i>';
                } else {
                    $collabString = null;
                }
                $attachCount = $tickets->countattachment;
                if ($attachCount > 0) {
                    $attachString = '&nbsp;<i class="fa fa-paperclip"></i>';
                } else {
                    $attachString = '';
                }
                $css = $tickets->css;
                $titles = '';
                if ($tickets->ticket_title) {
                    $titles = $tickets->ticket_title;
                }
                $tooltip_script = self::tooltip($tickets->id);
                return "<div class='tooltip1' id='tool" . $tickets->id . "'>
                            <a href='" . route('ticket.thread', [$tickets->id]) . "'>" . ucfirst($string) . "&nbsp;<span style='color:green'>(" . $tickets->countthread . ") <i class='" . $css . "'></i></span>
                            </a>" . $collabString . $attachString . $tooltip_script .
                "<span class='tooltiptext'  id='tooltip" . $tickets->id . "'>Loading...</span></div>";
            })
            ->addColumn('ticket_number', function ($tickets) {
                return "<a href='" . route('ticket.thread', [$tickets->id]) . "' title='" . $tickets->ticket_number . "'>#" . $tickets->ticket_number . '</a>';
            })
            ->addColumn('priority', function ($tickets) {
                $rep = ($tickets->last_replier == 'client') ? '#F39C12' : '#000';
                $priority = $tickets->priority;
                if ($priority != null) {
                    $prio = '<button class="btn btn-xs ' . $rep . '" style="background-color: ' . $tickets->priority_color . '; color:#F7FBCB">' . ucfirst($tickets->priority) . '</button>';
                } else {
                    $prio = $tickets->last_relier_role;
                }
                return $prio;
            })
            ->addColumn('user_name', function ($tickets) {
                $from = $tickets->first_name;
                $url = route('user.show', $tickets->user_id);
                $name = '';
                if ($from) {
                    $name = $tickets->first_name . ' ' . $tickets->last_name;
                } else {
                    $name = $tickets->user_name;
                }
                $color = '';
                if ($tickets->verified == 0 || $tickets->verified == '0') {
                    $color = "<i class='fa fa-exclamation-triangle'  title='" . Lang::get('lang.accoutn-not-verified') . "'></i>";
                }
                return "<a href='" . $url . "' title='" . Lang::get('lang.see-profile1') . ' ' . ucfirst($tickets->user_name) . '&apos;' . Lang::get('lang.see-profile2') . "'><span style='color:#508983'>" . ucfirst(str_limit($name, 30)) . ' <span style="color:#f75959">' . $color . '</span></span></a>';
            })
            ->addColumn('assign_user_name', function ($tickets) {
                if ($tickets->assigned_to == null) {
                    return "<span style='color:red'>Unassigned</span>";
                } else {
                    $assign = $tickets->assign_user_name;
                    $url = route('user.show', $tickets->assigned_to);
                    return "<a href='" . $url . "' title='" . Lang::get('lang.see-profile1') . ' ' . ucfirst($tickets->assign_first_name) . '&apos;' . Lang::get('lang.see-profile2') . "'><span style='color:green'>" . ucfirst($tickets->assign_first_name) . ' ' . ucfirst($tickets->assign_last_name) . '</span></a>';
                }
            })
            ->addColumn('updated_at', function ($tickets) {
                $TicketDatarow = $tickets->updated_at;
                $updated = '--';
                if ($TicketDatarow) {
                    $updated = $tickets->updated_at;
                }
                return '<span style="display:none">' . $updated . '</span>' . faveoDate($updated);
            })
            ->addColumn('created_at', function ($tickets) {
                $TicketDatarow = $tickets->created_at;
                $updated = '--';
                if ($TicketDatarow) {
                    $updated = $tickets->created_at;
                }
                return '<span style="display:none">' . $updated . '</span>' . faveoDate($updated);
            })
            ->make();
    }

    /**
     * @category function to call and show ticket details in tool tip via ajax
     *
     * @param null
     *
     * @return string //script to load tooltip data
     */
    public static function tooltip($ticketid)
    {
        return "<script>
                var timeoutId;
                $('#tool" . $ticketid . "').hover(function() {
                    if (!timeoutId) {
                        timeoutId = window.setTimeout(function() {
                        timeoutId = null; // EDIT: added this line
                                $.ajax({
                                url:'" . url('ticket/tooltip') . "',
                                dataType:'html',
                                type:'get',
                                data:{'ticketid':" . $ticketid . "},
                                success : function(html){
                                    $('#tooltip" . $ticketid . "').html(html);
                                },
                            });
                        }, 2000);
                    }
                },
                function () {
                    if (timeoutId) {
                        window.clearTimeout(timeoutId);
                        timeoutId = null;
                    } else {
                    }
                });
                </script>";
    }

    public function getTooltip(Request $request)
    {
        $ticketid = $request->input('ticketid');
        $ticket = Tickets::find($ticketid);
        $firstThread = $ticket->thread()->select('user_id', 'poster', 'body')->first();
        $lastThread = $ticket->thread()->select('user_id', 'poster', 'body')->orderBy('id', 'desc')->first();
        return '<b>' . $firstThread->user->user_name . ' (' . $firstThread->poster . ')</b></br>'
        . $firstThread->purify() . '<br><hr>'
        . '<b>' . $lastThread->user->user_name . '(' . $lastThread->poster . ')</b>'
        . $lastThread->purify() . '<br><hr>';
    }

    //Auto-close tickets
    public function autoCloseTickets()
    {
        $workflow = \App\Model\helpdesk\Workflow\WorkflowClose::whereId(1)->first();
        if ($workflow->condition == 1) {
            $overdues = Tickets::where('status', '=', 1)->where('isanswered', '=', 0)->orderBy('id', 'DESC')->get();
            if (count($overdues) == 0) {
                $tickets = null;
            } else {
                $i = 0;
                foreach ($overdues as $overdue) {
                    //                $sla_plan = Sla_plan::where('id', '=', $overdue->sla)->first();
                    $ovadate = $overdue->created_at;
                    $new_date = date_add($ovadate, date_interval_create_from_date_string($workflow->days . ' days')) . '<br/><br/>';
                    if (date('Y-m-d H:i:s') > $new_date) {
                        $i++;
                        $overdue->status = 3;
                        $overdue->closed = 1;
                        $overdue->closed_at = date('Y-m-d H:i:s');
                        $overdue->save();
                        //        if($workflow->send_email == 1) {
                        //             $this->PhpMailController->sendmail($from = $this->PhpMailController->mailfrom('0', $overdue->dept_id), $to = ['name' => $user_name, 'email' => $email], $message = ['subject' => $ticket_subject.'[#'.$ticket_number.']', 'scenario' => 'close-ticket'], $template_variables = ['ticket_number' => $ticket_number]);
                        //        }
                    }
                }
                // dd(count($value));
                //            if ($i > 0) {
                //                $tickets = new collection($value);
                //            } else {
                //                $tickets = null;
                //            }
            }
        } else {

        }
    }

    /**
     * @category function to chech if user verifcaition required for creating tickets or not
     *
     * @param null
     *
     * @return int 0/1
     */
    public function checkUserVerificationStatus()
    {
        $status = CommonSettings::select('status')
            ->where('option_name', '=', 'send_otp')
            ->first();
        if ($status->status == 0 || $status->status == '0') {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * This function is used for auto filling in new ticket.
     *
     * @return type view
     */
    public function autofill()
    {
        return view('themes.default1.agent.helpdesk.ticket.getautocomplete');
    }

    public function pdfThread($threadid)
    {
        try {
            $threads = new Ticket_Thread();
            $thread = $threads->leftJoin('tickets', 'ticket_thread.ticket_id', '=', 'tickets.id')
                ->leftJoin('users', 'ticket_thread.user_id', '=', 'users.id')
                ->where('ticket_thread.id', $threadid)
                ->first();
            //dd($thread);
            if (!$thread) {
                throw new Exception('Sorry we can not find your request');
            }
            $company = \App\Model\helpdesk\Settings\Company::where('id', '=', '1')->first();
            $system = \App\Model\helpdesk\Settings\System::where('id', '=', '1')->first();
            $ticket = Tickets::where('id', $thread->ticket_id)->first();
            $html = view('themes.default1.agent.helpdesk.ticket.thread-pdf', compact('thread', 'system', 'company', 'ticket'))->render();
            $html1 = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
            return PDF::load($html1)->show();
        } catch (Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public static function getSourceByname($name)
    {
        $sources = new Ticket_source();
        $source = $sources->where('name', $name)->first();
        return $source;
    }

    public static function getSourceById($sourceid)
    {
        $sources = new Ticket_source();
        $source = $sources->where('id', $sourceid)->first();
        return $source;
    }

    public static function getSourceCssClass($sourceid)
    {
        $css = 'fa fa-comment';
        $source = self::getSourceById($sourceid);
        if ($source) {
            $css = $source->css_class;
        }
        return $css;
    }

    public function getSystemDefaultHelpTopic()
    {
        $ticket_settings = new \App\Model\helpdesk\Settings\Ticket();
        $ticket_setting = $ticket_settings->find(1);
        $help_topicid = $ticket_setting->help_topic;
        return $help_topicid;
    }

    public function getSystemDefaultSla()
    {
        $ticket_settings = new \App\Model\helpdesk\Settings\Ticket();
        $ticket_setting = $ticket_settings->find(1);
        $sla = $ticket_setting->sla;
        return $sla;
    }

    public function getSystemDefaultPriority()
    {
        $ticket_settings = new \App\Model\helpdesk\Settings\Ticket();
        $ticket_setting = $ticket_settings->find(1);
        $priority = $ticket_setting->priority;
        return $priority;
    }

    public function getSystemDefaultDepartment()
    {
        $systems = new \App\Model\helpdesk\Settings\System();
        $system = $systems->find(1);
        $department = $system->department;
        return $department;
    }

    public function findTicketFromTicketCreateUser($result = [])
    {
        $ticket_number = $this->checkArray('0', $result);
        if ($ticket_number !== '') {
            $tickets = new \App\Model\helpdesk\Ticket\Tickets();
            $ticket = $tickets->where('ticket_number', $ticket_number)->first();
            if ($ticket) {
                return $ticket;
            }
        }
    }

    public function findUserFromTicketCreateUserId($result = [])
    {
        $ticket = $this->findTicketFromTicketCreateUser($result);
        if ($ticket) {
            $userid = $ticket->user_id;
            return $userid;
        }
    }

    public function checkArray($key, $array)
    {
        $value = '';
        if (array_key_exists($key, $array)) {
            $value = $array[$key];
        }
        return $value;
    }

    public function getAdmin()
    {
        $users = new \App\User();
        $admin = $users->where('role', 'admin')->first();
        return $admin;
    }

    public function attachmentSeperateOld($attach)
    {
        $attacment = [];
        if ($attach != null) {
            $size = count($attach);
            for ($i = 0; $i < $size; $i++) {
                $file_name = $attach[$i]->getClientOriginalName();
                $file_path = $attach[$i]->getRealPath();
                $mime = $attach[$i]->getClientMimeType();
                $attacment[$i]['file_name'] = $file_name;
                $attacment[$i]['file_path'] = $file_path;
                $attacment[$i]['mime'] = $mime;
            }
        }
        return $attacment;
    }

    public function attachmentSeperate($thread_id)
    {
        if ($thread_id) {
            $array = [];
            $attachment = new Ticket_attachments();
            $attachments = $attachment->where('thread_id', $thread_id)->get();
            if ($attachments->count() > 0) {
                foreach ($attachments as $key => $attach) {
                    $array[$key]['file_path'] = $attach->file;
                    $array[$key]['file_name'] = $attach->name;
                    $array[$key]['mime'] = $attach->type;
                    $array[$key]['mode'] = 'data';
                }
                return $array;
            }
        }
    }

    /**
     * @return type
     */
    public function followupTicketList()
    {
        try {
            $table = \Datatable::table()
                ->addColumn(
                    '', Lang::get('lang.subject'), Lang::get('lang.ticket_id'), Lang::get('lang.priority'), Lang::get('lang.from'), Lang::get('lang.assigned_to'), Lang::get('lang.last_activity'), Lang::get('lang.created-at'))
                ->noScript();
            return view('themes.default1.agent.helpdesk.followup.followup', compact('table'));
        } catch (Exception $e) {
            return Redirect()->back()->with('fails', $e->getMessage());
        }
    }

    public static function getSubject($subject)
    {
        //$subject = $this->attributes['title'];
        $array = imap_mime_header_decode($subject);
        $title = '';
        if (is_array($array) && count($array) > 0) {
            foreach ($array as $text) {
                $title .= $text->text;
            }
            return wordwrap($title, 70, "<br>\n");
        }
        return wordwrap($subject, 70, "<br>\n");
    }

    public function replyContent($content)
    {
        preg_match_all('/<img[^>]+>/i', $content, $result);
        $url = [];
        $encode = [];
        $img = [];
        foreach ($result as $key => $img_tag) {
            //dd($img_tag);
            preg_match_all('/(src)=("[^"]*")/i', $img_tag[$key], $img[$key]);
        }
        for ($i = 0; $i < count($img); $i++) {
            $url = $img[$i][2][0];
            $encode = $this->divideUrl($img[$i][2][0]);
        }
        return str_replace($url, $encode, $content);
    }

    public function divideUrl($url)
    {
        $baseurl = url('/');
        $trim = str_replace($baseurl, '', $url);
        $trim = str_replace('"', '', $trim);
        $trim = substr_replace($trim, '', 0, 1);
        $path = public_path($trim);
        return $this->fileContent($path);
    }

    public function fileContent($path)
    {
        $exist = \File::exists($path);
        $base64 = '';
        if ($exist) {
            $content = \File::get($path);
            $type = \File::extension($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($content);
        }
        return $base64;
    }

    /**
     * @category function to send notification of ticket merging to the owners
     *
     * @param srting array $t_id, $p_id
     *
     * @return null
     */
    public function sendMergeNotification($p_id, $t_id)
    {
        try {
            $ticket_details = Tickets::select('ticket_number', 'user_id', 'dept_id')->where('id', '=', $p_id)->first();
            $user_detail = User::where('id', '=', $ticket_details->user_id)->first();
            if ($user_detail->count() > 0) {
                if ($user_detail->email !== null || $user_detail->email !== '') {
                    $meged_ticket_details = Tickets::select('ticket_number')->whereIn('id', $t_id)->get();
                    $child_ticket_numbers = [];
                    foreach ($meged_ticket_details as $value) {
                        array_push($child_ticket_numbers, $value->ticket_number);
                    }
                    // dd(implode(", ",$child_ticket_numbers), $ticket_details->ticket_number);
                    $this->PhpMailController->sendmail($from = $this->PhpMailController->mailfrom('0', $ticket_details->dept_id), $to = ['user' => $user_detail->full_name, 'email' => $user_detail->email], $message = ['subject' => '', 'body' => '', 'scenario' => 'merge-ticket-notification'], $template_variables = ['user' => $user_detail->full_name, 'ticket_number' => $ticket_details->ticket_number, 'ticket_link' => route('ticket.thread', $p_id), 'merged_ticket_numbers' => implode(', ', $child_ticket_numbers)]);
                }
            }
        } catch (\Exception $e) {
            //catch the exception
        }
    }

    public function ticketChangeDepartment(Request $request)
    {
        if (!$this->ticket_policy->transfer()) {
            if ($api) {
                return response()->json(['message' => 'permission denied'], 403);
            }
            return redirect('dashboard')->with('fails', 'Permission denied');
        }
        $match_dept_name = Department::where('name', '=', $request->tkt_dept_transfer)->select('id')->first();
        if (!$match_dept_name) {
            return redirect()->back()->with('fails', Lang::get('lang.this_deparment_not_exists'));
        } else {
            $ticket_id = $request->tkt_id;
            $ticket = Tickets::findOrFail($ticket_id);
            $ticket->dept_id = $match_dept_name->id;
            $sla = $this->getSla($ticket->type, $ticket->user_id, $match_dept_name->id, $ticket->source, $ticket->priority_id);
            $ticket = $this->updateOverdue($ticket, $sla);
            $ticket->save();
            return redirect()->back()->with('success', Lang::get('lang.ticket_department_successfully_changed'));
        }
    }

}