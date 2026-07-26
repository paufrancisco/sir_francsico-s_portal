print_r(DB::table('migrations')->orderByDesc('id')->limit(3)->get()->toArray());
