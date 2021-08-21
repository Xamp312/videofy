<?php

namespace App\Http\Controllers;

use Image;
use App\Models\Category;
use App\Models\Video;
use App\Models\Vote;
use App\Models\Rate;
use App\Models\User;

use Auth;
use Illuminate\Http\Request;

class VideoController extends Controller
{

    public function upload(Request $request)
    {

        $video = new Video();
        $video->title = $request->video_title;
        $video->description = $request->description;
        $video->is_private = $request->is_private;

        $video->user_id = Auth::id();

        $category = Category::find($request->category_id);
        $category->count += 1;
        $category->save();

        $video->category_id = $category->id;

        $video->save();
        $slug = $video->slug;

        if($request->hasFile('image')){

            $image = $request->file('image');
            $filename = $slug. '.' . $image->getClientOriginalExtension();
            $image = Image::make($image);

            $image->save('uploads/thumbnails/' . $filename);

            $video->image_name = $filename;

        }

        if($request->hasFile('video')){
            $video1 = $request->file('video');
            $filename = $slug. '.' . $video1->getClientOriginalExtension();

            $path = public_path('/uploads/videos');
            $video1->move($path, $filename);
            $video->video_name = $filename;

        }

//             php.ini files contains some limits that might affect this. Try changing these to high enough values:

// upload_max_filesize = 10M
// post_max_size = 10M
// memory_limit = 32M

        $video->save();

        return redirect()->back()->with([
            'success' => 'You video has been succesfully uploaded.',
        ]);

    }

    public function mostViewed()
    {
        $videos = Video::orderBy('views', 'desc')->get();

        return view('mostViewed', compact('videos'));

    }

    public function latestVideos()
    {

        $videos = Video::orderby('created_at', 'desc')->get();
        $category = Category::all();

        return view('pages.latestVideos', compact('videos', 'category'));
    }

    public function topRated()
    {
        //
    }

    public function videoPage($slug)
    {


        $video = Video::where('slug', $slug)->first();

        if(Auth::check()){
            $user = User::find(Auth::id()); 
            $user->video_id = $video->id; 
            $user->save();
        }


        $video->views += 1;
        $video->save();

        $video->likes = Vote::where('video_id', $video->id)
            ->where('react', '1')
            ->get();

        $video->dislikes = Vote::where('video_id', $video->id)
            ->where('react', '2')
            ->get();


        $usersOnVideo = User::where('video_id', $video->id)
                                ->get();


        $usersOnVideoOnline = array();

        foreach($usersOnVideo as $uOV) {
            if($uOV->isOnline()){
                array_push($usersOnVideoOnline, $uOV);
            }
        }


        
        return view('pages.video')->with([
            'video' => $video,
            'usersOnVideoOnline' => $usersOnVideoOnline
        ]);
    }

    public function videoReact(Request $request)
    {

        $videoReact = Vote::where('video_id', $request->videoId)
            ->where('user_id', Auth::id())
            ->delete();

        $newVote = new Vote;
        $newVote->video_id = $request->videoId;
        $newVote->user_id = Auth::id();
        if ($request->react == 1) {
            $newVote->react = 1;
        } else {
            $newVote->react = 2;
        }

        $newVote->save();
    }


    public function videoRate(Request $request)
    {

        $videoRate = Rate::where('video_id', $request->videoId)
            ->where('user_id', Auth::id())
            ->delete();


        $newRate = new Rate;
        $newRate->video_id = $request->videoId;
        $newRate->user_id = Auth::id();
        $newRate->rate = $request->ratings;
    

        $newRate->save();
    }



    public function videoUploadPage()
    {

        $category = Category::all();
        return view('pages.uploadVideo')->with([
            'category' => $category,
        ]);
    }

    public function profile($slug){

        $user = User::where('slug', $slug)->first();

    if(Auth::user() == $user){
        return redirect()->route('home');
    }

    else{

        return view('pages.profile',compact('user'));

         
    }
    }


}
