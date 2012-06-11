package sc_parser;

sub new
{
	my $self = {};

	bless $self;

	$self->{'dl'} = {};
	$self->{'tl'} = {};

	return $self;
}

sub DESTROY
{
	my $self = shift;

	# alles?
}

sub set_name_syns(\%\%) # driver synonyms, team synonyms
{
	my $self = shift;

	my $dr_syn = shift;
	my $tm_syn = shift;

	$self->{'dr_syn'} = { %$dr_syn };
	$self->{'tm_syn'} = { %$tm_syn };
}

sub parse_scores(@) # directories with scores
{
	my $self = shift;
	my $r_cnt;
	my $dl = $self->{'dl'};
	my $tl = $self->{'tl'};

	$r_cnt = 0;
	foreach $r (@_) {
		my $l_cnt;

		$r_cnt++;
		$@ = "open $r/score failed", return 0 if (!open(SF, "<$r/score"));

		# drivers
		$l_cnt = 0;
		while (defined($l = <SF>) && (chomp($l), $l ne "")) {
			my $d;
			my $dd;

			$l_cnt++;
			$@ = "line $l doesn't match driver-line", return 0 if ($l !~ /^\s*(.*?)\s*:\s+(.*?)\s*$/);
			$d = $1; $d = $self->{'dr_syn'}->{$d} if (defined $self->{'dr_syn'}->{$d});

			if (!defined $dl->{$d}) {
				$dl->{$d} = {};
				$dl->{$d}->{'name'} = $d;
				$dl->{$d}->{'rac'} = {};
			}
			$dd = $dl->{$d};
			$dd->{'rac'}->{$r} = {};
			$dd->{'rac'}->{$r}->{'c'} = $r_cnt;
			$dd->{'rac'}->{$r}->{'l'} = $l_cnt;
			$dd->{'rac'}->{$r}->{'p'} = $2;
			#$dd->{'rac'}->{$r}->{'u'} = 0; #undefined
		}

		# teams
		$l_cnt = 0;
		while (defined($l = <SF>) && (chomp($l), $l ne "")) {
			my $d;
			my $dd;

			$l_cnt++;
			$@ = "line $l doesn't match team-line", return 0 if ($l !~ /^\s*(.*?)\s*:\s+(.*?)\s*$/);
			$d = $1; $d = $self->{'tm_syn'}->{$d} if (defined $self->{'tm_syn'}->{$d});

			if (!defined $tl->{$d}) {
				$tl->{$d} = {};
				$tl->{$d}->{'name'} = $d;
				$tl->{$d}->{'rac'} = {};
			}
			$dd = $tl->{$d};
			$dd->{'rac'}->{$r} = {};
			$dd->{'rac'}->{$r}->{'c'} = $r_cnt;
			$dd->{'rac'}->{$r}->{'l'} = $l_cnt;
			$dd->{'rac'}->{$r}->{'pl'} = [ split(/\+/, $2) ];
			#$dd->{'rac'}->{$r}->{'u'} = 0; #undefined
		}
		close(SF);
	}

	return 1;
}

sub dr_sorter_sub
{
	my $ra = $a->{'rac'};	# tmp, full result hash for a
	my $rb = $b->{'rac'};	# tmp, full result hash for b
	my @ra = values %$ra;	# full result array
	my @rb = values %$rb;	# full result array
	my ($ma, $mb);		# the best result
	my ($ca, $cb);		# counter for best results
	my ($fa, $fb);		# first of best results

	return $b->{'totc'} <=> $a->{'totc'} if ($b->{'totc'} != $a->{'totc'});
	return $b->{'totr'} <=> $a->{'totr'} if ($b->{'totr'} != $a->{'totr'});

	$ma = 0;
	foreach $t (@ra) {
		next if ($t->{'p'} < $ma);
		if ($t->{'p'} > $ma) {
			$ca = 0;
			$ma = $t->{'p'};
			$fa = $t->{'c'};
		}
		$ca++;
	}
	$mb = 0;
	foreach $t (@rb) {
		next if ($t->{'p'} < $mb);
		if ($t->{'p'} > $mb) {
			$cb = 0;
			$mb = $t->{'p'};
			$fb = $t->{'c'};
		}
		$cb++;
	}

	return $mb <=> $ma if ($mb != $ma);
	return $cb <=> $ca if ($cb != $ca);
	return $fa <=> $fb if ($fa != $fb);
	die "probably internal error... $a->{'name'} $b->{'name'} $fa $fb\n";
}

sub sort_drivers($) # count only arg1 best races
{
	my $self = shift;
	my $max_cnt = shift;
	my $dl = $self->{'dl'};
	my $dd;

	foreach $dd (values %$dl) {
		my $t = $dd->{'rac'};
		my @rlc = values %$t;
		my @rlr;

		@rlc = sort { ($b->{'p'} != $a->{'p'})?($b->{'p'} <=> $a->{'p'}):($a->{'c'} <=> $b->{'c'}) } @rlc;
		@rlr = splice(@rlc, $max_cnt) if ($#rlc+1 > $max_cnt);
		$dd->{'totc'} = 0;
		foreach $s (@rlc) {
			$s->{'u'} = 1;
			$dd->{'totc'} += $s->{'p'};
		}
		$dd->{'totr'} = 0;
		foreach $s (@rlr) {
			$s->{'u'} = 0;
			$dd->{'totr'} += $s->{'p'};
		}
	}

	return sort dr_sorter_sub (values %$dl);
}

sub tm_sorter_sub
{
	my $ra = $a->{'rac'};	# tmp, full result hash for a
	my $rb = $b->{'rac'};	# tmp, full result hash for b
	my @ra = values %$ra;	# full result array
	my @rb = values %$rb;	# full result array
	my ($ma, $mb);		# the best result
	my ($ca, $cb);		# counter for best results
	my ($fa, $fb);		# first of best results

	return $b->{'totc'} <=> $a->{'totc'} if ($b->{'totc'} != $a->{'totc'});
	return $b->{'totr'} <=> $a->{'totr'} if ($b->{'totr'} != $a->{'totr'});

	$ma = 0;
	foreach $t (@ra) {
		my $pl = $t->{'pl'};
		$pl = (sort(@$pl))[0];
		next if ($pl < $ma);
		if ($pl > $ma) {
			$ca = 0;
			$ma = $pl;
			$fa = $t->{'c'};
		}
		$ca++;
	}
	$mb = 0;
	foreach $t (@rb) {
		my $pl = $t->{'pl'};
		$pl = (sort(@$pl))[0];
		next if ($pl < $mb);
		if ($pl > $mb) {
			$cb = 0;
			$mb = $pl;
			$fb = $t->{'c'};
		}
		$cb++;
	}

	return $mb <=> $ma if ($mb != $ma);
	return $cb <=> $ca if ($cb != $ca);
	return $fa <=> $fb if ($fa != $fb);
	die "probably internal error... $a->{'name'} $b->{'name'} $fa $fb\n";
}

sub sort_teams($$) # count only arg1 best races, and only arg2 drivers from each race
{
	my $self = shift;
	my $max_cnt = shift;
	my $max_drv = shift;
	my $dl = $self->{'tl'};
	my $dd;

	foreach $dd (values %$dl) {
		my $r;
		my $t = $dd->{'rac'};
		my @rlc = values %$t;
		my @rlr;

		# first compute total ('p') for each race
		foreach $r (@rlc) {
			my $t = $r->{'pl'};
			my @dlc = sort { $b <=> $a } @$t;

			splice(@dlc, $max_drv) if ($#dlc+1 > $max_drv);
			$r->{'p'} = 0;
			foreach $t (@dlc) {
				$r->{'p'} += $t;
			}
		}

		@rlc = sort { ($b->{'p'} != $a->{'p'})?($b->{'p'} <=> $a->{'p'}):($a->{'c'} <=> $b->{'c'}) } @rlc;
		@rlr = splice(@rlc, $max_cnt) if ($#rlc+1 > $max_cnt);
		$dd->{'totc'} = 0;
		foreach $s (@rlc) {
			$s->{'u'} = 1;
			$dd->{'totc'} += $s->{'p'};
		}
		$dd->{'totr'} = 0;
		foreach $s (@rlr) {
			$s->{'u'} = 0;
			$dd->{'totr'} += $s->{'p'};
		}
	}

	return sort tm_sorter_sub (values %$dl);
	#return sort dr_sorter_sub (values %$dl);
	#return sort { $b->{'totc'} <=> $a->{'totc'} } values %$dl;
}

$| = 1;
1;
