<?
function ConnectedComponentsPoolAlloc(array $t_args, array $inputs, array $outputs)
{
    // Class name is randomly generated
    $className = generate_name("CCompGLA");

    // Processing of inputs.
    grokit_assert(count($inputs) == 2, 'Connected Components: 2 inputs expected');
    $inputs_ = array_combine(['src', 'dst'], $inputs);

    // Setting output type
    $outType = lookupType('int');
    $outputs_ = ['node' => $outType, 'component' => $outType];
    $outputs = array_combine(array_keys($outputs), $outputs_);

    $sys_headers = ["vector", "utility", "functional", "boost/functional/hash.hpp", "unordered_map", "boost/pool/pool_alloc.hpp"];
    $user_headers = [];
    $lib_headers = [];
?>

using namespace std;

class <?=$className?>;

class <?=$className?> {
 //typedef std::unordered_map<uint64_t, uint64_t, boost::hash<uint64_t>, 
        //std::equal_to<uint64_t>, boost::pool_allocator<std::pair<uint64_t const, uint64_t>>> UFData;
 typedef std::unordered_map<uint64_t, uint64_t> UFData;
 class UnionFindMap{
  private:
    UFData* parent; 
    UFData sz;

    const uint64_t NON_EXISTING_ID = -1;
  public:
    // constructor did nothing
    UnionFindMap(){
      parent = new UFData();
    }

    uint64_t Find(uint64_t i){
      if ((*parent).find(i) == (*parent).end()){
        return NON_EXISTING_ID;
      }
      // use path compression here
      while (i != (*parent)[i]){
        (*parent)[i] = (*parent)[(*parent)[i]];
        i = (*parent)[i];
      }
      return i;
    }

    // put merge small tree into higher tree
    // if disjoint, merge and return false
    void Union(uint64_t i, uint64_t j){
      uint64_t ip = Find(i);
      uint64_t jp = Find(j);

      if (ip != NON_EXISTING_ID && jp != NON_EXISTING_ID){// both exists
        if (ip != jp){
          if (sz[ip] < sz[jp]){
            (*parent)[ip] = jp; sz[jp] += sz[ip];
          }else{
            (*parent)[jp] = ip; sz[ip] += sz[jp];
          }
        }
      }else if(ip == NON_EXISTING_ID && jp == NON_EXISTING_ID){// both new
        (*parent)[i] = i; sz[i] = 2;
        (*parent)[j] = i; 
      }else if (jp == NON_EXISTING_ID){ // i exists
        (*parent)[j] = ip; sz[ip] ++;
      }else{
        (*parent)[i] = jp; sz[jp] ++;
      }
    }

    UFData* GetUF(){
      return parent;
    }

    bool IsEmpty(){
      return (*parent).empty();
    }

    uint64_t GetSize(){
      return (uint64_t) (*parent).size(); 
    }

    void SetData(UFData* other_data){
      parent = other_data;
    }

    // 
    void FinalizeRoot(){
      for(UFData::iterator it = (*parent).begin(); it != (*parent).end(); ++ it){
        it->second = Find(it->first);
      }
    }

    void Clear(){
      (*parent).clear();
      sz.clear();
    }
    
    ~UnionFindMap(){
        delete parent;
    }

 };

 private:
  // union-find map data structure, which contains nodeID->compID information
  UnionFindMap primary_uf;
  UFData::iterator output_iterator, output_iterator_end;
  bool localFinalized = false;
 public:
  <?=$className?>() {}

  void AddItem(<?=const_typed_ref_args($inputs_)?>) {
    uint64_t src_ = Hash(src);
    uint64_t dst_ = Hash(dst);
  
    primary_uf.Union(src_, dst_);
  }

  void AddState(<?=$className?> &other) {
      FinalizeLocalState();
      other.FinalizeLocalState();
      UFData* this_state_data = primary_uf.GetUF();
      UFData* other_state_data = other.primary_uf.GetUF();

      if (primary_uf.GetSize() < other.primary_uf.GetSize()){
        UFData* tmp = this_state_data;
        this_state_data = other_state_data;
        other_state_data = tmp;

        primary_uf.SetData(this_state_data);
        other.primary_uf.SetData(other_state_data);
      }
      assert(primary_uf.GetSize() >= other.primary_uf.GetSize());
      
      UnionFindMap secondary_uf;
      //go over the other state, and maintain a secondary table
      for(auto const& entry:(*other_state_data)){
        if ((*this_state_data).count(entry.first) == 1){// key exists in this state
          uint64_t this_comp_id = (*this_state_data)[entry.first];
          if (this_comp_id != entry.second) // merge needed
            secondary_uf.Union(this_comp_id, entry.second);
        }else{
          (*this_state_data)[entry.first] = entry.second;
        }
      }

      // check if side table empty
      if (secondary_uf.IsEmpty()){
        return;
      }

      // apply the side table
      secondary_uf.FinalizeRoot();
      UFData* secondary_state_data = secondary_uf.GetUF();
      for (auto& p:(*this_state_data)){
        if ((*secondary_state_data).find(p.second) != (*secondary_state_data).end()){
          p.second = (*secondary_state_data)[p.second];
        }
      }
      
  }

  void FinalizeLocalState(){
  	if (!localFinalized){
  		primary_uf.FinalizeRoot();
  		localFinalized = true;
  	}
  }

  void Finalize(){
      output_iterator = primary_uf.GetUF()->begin();
      output_iterator_end = primary_uf.GetUF()->end();
  }

  bool GetNextResult(<?=typed_ref_args($outputs_)?>) {
      
      if (output_iterator != output_iterator_end){
        node = output_iterator->first;
        component = output_iterator->second;
        
        ++ output_iterator;
        return true;
      }else{
        return false;
      }
 }
};

<?
    return [
        'kind'           => 'GLA',
        'name'           => $className,
        'system_headers' => $sys_headers,
        'user_headers'   => $user_headers,
        'lib_headers'    => $lib_headers,
        'input'          => $inputs,
        'output'         => $outputs,
        'result_type'    => 'multi',
    ];
}
?>
