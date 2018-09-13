<?php
    namespace App\Services\Member;

    use App\Models\Member;
    use App\Models\MemberBooks;
    use App\Models\NovelBase;
    use App\Models\NovelDetail;
    use App\Models\NovelContent;

    use App\Services\BaseService;

    class MemberService extends BaseService {


        public static function memberRegister($data){
            $data['last_login_ip'] = $data['register_ip'];

            $data['password'] = password_hash($data['password'],PASSWORD_BCRYPT );
            if(!$data['password']) {
                static::addError('注册失败，请重试',0);
                return false;
            }
            unset($data['password_confirm']);

            try{
                Member::create($data);
            }catch(\Exception $e){
                static::addError('注册失败，请稍后再试',0);
                return false;
            }
            return true;
        }

        public static function loginCheck($user_name,$password,$login_ip){
            $user = Member::where('username',$user_name)->first();
            if(!$user) {
                static::addError('账号未注册',0);
                return false;
            }

            if(!password_verify($user->password,$password)){
                static::addError('密码错误，请重试',0);
                return false;
            }
            
            $user->last_login_ip = $login_ip;
            $user->save();
            return $user;
        }

        public static function memberBooks($member_id){
            if(!$member_id) {
                static::addError('参数不完整',0);
                return false;
            }

            $search = [
                'member_id' => $member_id,
                'is_collection' => 1
            ];
            $novel_ids = MemberBooks::where($search)->orderBy('created_at','asc')->pluck('novel_id')->get();
            if(!$novel_ids) {
                static::addError('书架空空如也',0);
                return false;
            }
            $novels = NovelBase::whereIn('id',$novel_ids)->where('is_hide',0)->get();

            return $novels;
        }


        public static function memberReadBookCapter($novel_id){
            $read_record = MemberBooks::where('novel_id',$novel_id)->first();
            if(!$read_record) {
                static::addError('注册失败，请重试',0);
                return false;
            }

            $novel_detail = NovelDetail::find($read_record->capter_id);
            if(!$novel_detail || !$novel_detail->is_update){
                static::addError('该章节还未更新',0);
                return false;
            }

            $content = NovelContent::where('capter_id',$novel_detail->id)->first();
            if(!$content) {
                static::addError('该章节还未更新',0);
                return false;
            }

            $novel_detail->content = $content->content;
            return $novel_detail;
            
        }
       
    }