echo "Row count: " . DB::table('topics')->count() . "\n";
print_r(DB::table('topics')->limit(3)->get()->toArray());
