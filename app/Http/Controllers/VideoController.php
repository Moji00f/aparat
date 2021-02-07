<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Video;
use App\Models\Playlist;
use Illuminate\Support\Str;
use FFMpeg\Format\Video\X264;
use App\Events\UploadeNewVideo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use FFMpeg\Filters\Video\CustomFilter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use App\Http\Requests\Video\UploadVideoRequest;
use App\Http\Requests\Video\CreateVideoRequest;
use App\Http\Requests\Video\UploadVideoBannerRequest;

class VideoController extends Controller
{
    /**
     * آپلود ویدیو به صورت موقت
     */
    public function uploadVideo(UploadVideoRequest $request)
    {
        try {
            $video = $request->file('video');
            $fileName = time() . '_' . Str::random(10);
            Storage::disk('videos')->put('temp/' . $fileName, $video->get());

            return response(['video' => $fileName], Response::HTTP_OK);
        } catch (Exception $exception) {
            Log::info($exception);

            return response(['message' => 'خطایی در سمت سرور رخ داده است.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * آپلود کردن بنر برای ویدیو
     */
    public function uploadBanner(UploadVideoBannerRequest $request)
    {
        try {
            $banner = $request->file('banner');
            $fileName = time() . '_' . Str::random(10) . '-banner';
            Storage::disk('videos')->put('temp/' . $fileName, $banner->get());

            return response(['banner' => $fileName], Response::HTTP_OK);
        } catch (Exception $exception) {
            Log::info($exception);

            return response(['message' => 'خطایی در سمت سرور رخ داده است.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * انتشار ویدیو و ذخیره اطلاعات در دیتابیس
     */
    public function createVideo(CreateVideoRequest $request)
    {
        try {
            DB::beginTransaction();

            $video = Video::create([
                'title' => $request->title,
                'user_id' => auth()->id(),
                'category_id' => $request->category_id,
                'channel_category_id' => $request->channel_category,
                'slug' => '',
                'info' => $request->info,
                'duration' => 0,
                'banner' => null,
                'enable_comments' => $request->enable_comments,
                'publish_at' => $request->publish_at,
                'state'=>Video::STATE_PENDING
            ]);

            $video->slug = uniqid($video->id);
            $video->banner = $video->slug . '-banner';
            $video->save();

            event(new UploadeNewVideo($video, $request));

            if ($request->banner) {
                $banner = $request->video_id . '-banner';
                Storage::disk('videos')->move('temp/' . $request->banner, auth()->id() . '/' . $video->banner);
            }

            if ($request->playlist) {
                $playlist = Playlist::find($request->playlist);
                $playlist->videos()->attach($video->id);
            }

            if (! empty($request->tags)) {
                $video->tags()->attach($request->tags);
            }

            DB::commit();

            return response([$video], Response::HTTP_OK);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info($exception);

            return response(['message' => 'خطایی در سمت سرور رخ داده است.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
