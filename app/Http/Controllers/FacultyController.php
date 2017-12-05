<?php

namespace App\Http\Controllers;

use App\Facultylogin;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\Types\Null_;

class FacultyController extends Controller
{

    public function index1()
    {
        if (isset($_REQUEST['status']))
            return view('Faculty.search', compact($_REQUEST['status']));
        else
            return view('Faculty.search');
    }

    public function index3()
    {
        $userid = session('username');
        $cat = Facultylogin::where('userid', $userid)->first()->category;
        if ($cat == 'ADMIN' or $cat == 'HOD')
            return view('Faculty.searchupdate');
        else
            return view('errors.FacultyNotAllowed');
    }


    public function index()
    {
        $uid = $_REQUEST['fac_id'];

        $cat = Facultylogin::where('userid', session('username'))->first()->category;
        if ($cat == "HOD") {
            $branch = Facultylogin::where('userid', session('username'))->first()->branch;

            $data = Facultylogin::where('userid', $uid)->first();
            $cats = Facultylogin::select('branch')->distinct()->get();
            $cat = Facultylogin::where('userid', session('username'))->first()->category;

            if (isset($data->userid) and $data->userid == $uid) {
                if ($data->branch == $branch) {
                    return view('Faculty.UpdateFaculty', compact('data', 'cats'))
                        ->with('cat', $cat);
                } else {
                    return view('Faculty.HodCantAccessFaculty');
                }
            } else {
                return view('Faculty.NotFound');
            }
        } elseif ($cat == "ADMIN") {
            $data = Facultylogin::where('userid', $uid)->first();
            $cats = Facultylogin::select('branch')->distinct()->get(['branch']);
            $cat = Facultylogin::where('userid', session('username'))->first()->category;
            if (isset($data->userid) and $data->userid == $uid) {
                return view('Faculty.UpdateFaculty', compact('data', 'cats'))
                    ->with('cat', $cat);
            } else {
                return view('Faculty.NotFound');
            }
        } else {
            return "Unauthorised Access";
        }
    }

    public function facupdate()
    {
        $uid = $_REQUEST['fac_id'];

        $cat = Facultylogin::where('userid', session('username'))->first()->category;
        if ($cat == "HOD") {
            $branch = Facultylogin::where('userid', session('username'))->first()->branch;
            $faculties = Facultylogin::where('userid', $uid)->first();
            if (!isset($faculties->userid) or $faculties->userid == Null)
                return view('Faculty.NotFound');
            elseif ($branch == $faculties->branch)
                return view('Faculty.ShowAllUpdate', compact('faculties'));
            else
                return view('Faculty.HodCantAccessFaculty');
        } elseif ($cat == "ADMIN") {
            $faculties = Facultylogin::where('userid', $uid)->first();
            if (isset($faculties->userid) and $faculties->userid == $uid)
                return view('Faculty.ShowAllUpdate', compact('faculties'));
            else
                return view('Faculty.NotFound');
        } else {
            return view('errors.FacultyNotAllowed');
        }
    }


    public function create()
    {
        $cat = Facultylogin::where('userid', session('username'))->first()->category;
        $branch = Facultylogin::where('userid', session('username'))->first()->branch;
        if ($cat == 'ADMIN') {
            $cats = Facultylogin::select('branch')->distinct()->get();
            return view('Faculty.AddFaculty')
                ->with('cat', $cat)
                ->with('cats', $cats)
                ->with('branch', $branch);
        } elseif ($cat == 'HOD') {
            $NewId = Facultylogin::select('userid')->where([['branch', $branch], ['category', 'FACULTY']])->orderBy('userid', 'DESC')->first();
            $id = trim($NewId->userid, 'cse');


            $id = strtolower($branch) . '0' . ($id + 1);
            return view('Faculty.AddFaculty')
                ->with('cat', $cat)
                ->with('branch', $branch)
                ->with('newId', $id);
        } else {
            return "UNAUTHORISED ACCESS !!";
        }
    }

    public function store(Request $request)
    {
        $check = Facultylogin::where('userid', $request->userid)->get();

        if ($check == NULL) {
            return redirect('/managefaculty/create?errors=AlreadyExist');
        }

        $str = $request->sal . ' ' . $request->name;

        Facultylogin::create([
            'branch' => $request->branch,
            'category' => $request->category,
            'name' => $str,
            'userid' => $request->userid
        ]);
        return redirect('/managefaculty/create?errors=Sucess');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $data = Facultylogin::where('userid', $id)->first();
        $cats = Facultylogin::select('branch')
            ->distinct()
            ->orderBy('branch', 'asc')
            ->get(['branch']);
        $cat = Facultylogin::where('userid', session('username'))
            ->first()
            ->category;

        if (isset($data->password) and $data->password != null) {
            if ($cat == 'ADMIN' and isset($data->userid) and $data->userid == $id) {
                return view('Faculty.UpdateFaculty', compact('data', 'cats'))
                    ->with('cat', $cat);
            }
            if ($cat == 'HOD') {
                $branch = Facultylogin::where('userid', session('username'))->first()->branch;
                if ($branch == $data->branch) {
                    return view('Faculty.UpdateFaculty', compact('data', 'cats'))
                        ->with('cat', $cat);
                } else {
                    return view('errors.HodForSameBranchOnly');
                }
            } else {
                return view('errors.FacultyNotAllowed');
            }
        } else {
            return view('Faculty.NotFound');
        }
    }

    public function update(Request $request, $id)
    {
        Facultylogin::where('userid', $id)->update($request->except('_token', '_method'));
        return redirect('/managefaculty2?status=success');
    }

    public function destroy($id)
    {
        Facultylogin::where('userid', $id)->delete();
        return redirect('/managefaculty2');
    }

    public function getNewId()
    {
        $branch = $_REQUEST['branch'];
        $NewId = Facultylogin::where('branch', $branch)
            ->distinct()
            ->orderBy('userid', 'desc')
            ->first()->userid;
        $next = trim($NewId, $branch);
        $next = trim($next, strtolower($branch));
        if (is_int($next))
            return strtolower($branch) . ($next + 1);
        else
            return strtolower($branch) . $next;
    }
}