<?
function ConnectedComponents(array $t_args, array $inputs, array $outputs)
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

    $sys_headers = ['unordered_map', 'set', 'vector'];
    $user_headers = [];
    $lib_headers = [];
?>

using namespace std;

class <?=$className?>;

class <?=$className?> {
 
 class UnionFindMap{
  private:
    std::unordered_map<uint64_t, uint64_t> parent; 
    std::unordered_map<uint64_t, uint64_t> sz;
    const uint64_t NON_EXISTING_ID = -1;
  public:
    // constructor did nothing
    UnionFindMap(){}

    /*void Init(std::unordered_map<uint64_t, uint64_t>& parent){
      this->parent = parent;
    }*/

    uint64_t Find(uint64_t i){
      if (parent.find(i) == parent.end()){
        return NON_EXISTING_ID;
      }
      // use path compression here
      while (i != parent[i]){
        parent[i] = parent[parent[i]];
        i = parent[i];
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
            parent[ip] = jp; sz[jp] += sz[ip];
          }else{
            parent[jp] = ip; sz[ip] += sz[jp];
          }
        }
      }else if(ip == NON_EXISTING_ID && jp == NON_EXISTING_ID){// both new
        parent[i] = i; sz[i] = 2;
        parent[j] = i; 
      }else if (jp == NON_EXISTING_ID){ // i exists
        parent[j] = ip; sz[ip] ++;
      }else{
        parent[i] = jp; sz[jp] ++;
      }
    }

    std::unordered_map<uint64_t, uint64_t>& GetUF(){
      return parent;
    }

    // 
    void FinalizeRoot(){
      for(std::unordered_map<uint64_t, uint64_t>::iterator it = parent.begin(); it != parent.end(); ++ it){
        it->second = Find(it->first);
      }
    }

    void Clear(){
      parent.clear();
      sz.clear();
    }

 };

 private:
  // union-find map data structure, which contains nodeID->compID information
  UnionFindMap localUF, globalUF;
  std::unordered_map<uint64_t, uint64_t>::iterator OutputIterator, EndOfOutput;
  bool localFinalized = false;
 public:
  <?=$className?>() {}

  void AddItem(<?=const_typed_ref_args($inputs_)?>) {
    uint64_t src_ = Hash(src);
    uint64_t dst_ = Hash(dst);
  
    localUF.Union(src_, dst_);
  }

  void AddState(<?=$className?> &other) {
      FinalizeLocalState();
      other.FinalizeLocalState();
      std::unordered_map<uint64_t, uint64_t>& compIDLocal = localUF.GetUF();
      std::unordered_map<uint64_t, uint64_t>& otherState = other.localUF.GetUF();

      for(auto const& entry:otherState){
        if (compIDLocal.find(entry.first) != compIDLocal.end()
            && compIDLocal[entry.first] != entry.second){ // merge needed
          globalUF.Union(compIDLocal[entry.first], entry.second);
        }else{
          compIDLocal[entry.first] = entry.second;
        }
      }

      // merge local and global
      globalUF.FinalizeRoot();
      std::unordered_map<uint64_t, uint64_t>& compIDGlobal = globalUF.GetUF();
      for (auto& p:compIDLocal){
        if (compIDGlobal.find(p.second) != compIDGlobal.end()){
          p.second = compIDGlobal[p.second];
        }
      }
      globalUF.Clear();
  }

  void FinalizeLocalState(){
	if (!localFinalized){
		localUF.FinalizeRoot();
		localFinalized = true;
	}
  }

  void Finalize(){
      OutputIterator = localUF.GetUF().begin();
      EndOfOutput = localUF.GetUF().end();
  }

  bool GetNextResult(<?=typed_ref_args($outputs_)?>) {
      if (OutputIterator != EndOfOutput){
        node = OutputIterator->first;
        component = OutputIterator->second;
        ++ OutputIterator;
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
